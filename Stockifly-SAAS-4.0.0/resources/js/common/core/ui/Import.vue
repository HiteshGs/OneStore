<template>
  <div>
    <a-button type="primary" @click="showModal">
      <CloudDownloadOutlined />
      {{ pageTitle }}
    </a-button>

    <a-modal v-model:open="visible" :title="pageTitle">
      <!-- Sample file -->
      <a-row :gutter="16" class="mb-10">
        <a-col :xs="24">
          <a-typography-paragraph>
            <ul>
              <li>
                <a-typography-link :href="sampleFileUrl" target="_blank">
                  {{ $t("messages.click_here_to_download_sample_file") }}
                </a-typography-link>
              </li>
            </ul>
          </a-typography-paragraph>
        </a-col>
      </a-row>

      <!-- Upload + optional options -->
      <a-row :gutter="16" class="mb-10">
        <a-col :xs="24">
          <a-form layout="vertical">
            <a-form-item
              :label="$t('common.file')"
              name="file"
              :help="rules.file ? rules.file.message : null"
              :validateStatus="rules.file ? 'error' : null"
            >
              <a-upload
                :accept="'.xlsx,.csv'"
                v-model:file-list="fileList"
                name="file"
                :before-upload="beforeUpload"
                :maxCount="1"
              >
                <a-button :loading="loading">
                  <template #icon><UploadOutlined /></template>
                  {{ $t("common.upload") }}
                </a-button>
              </a-upload>
            </a-form-item>

            <a-collapse v-if="!hideOptions" class="mb-2">
              <a-collapse-panel :header="$t('common.options')" key="opts">
                <a-space direction="vertical" style="width: 100%">
                  <a-form-item :label="$t('product.store_unknown_columns_as_custom_fields')">
                    <a-switch v-model:checked="storeUnknownAsCustom" />
                  </a-form-item>

                  <a-form-item :label="$t('product.auto_create_missing_category_brand_unit')">
                    <a-switch v-model:checked="autoCreateMasters" />
                  </a-form-item>

                  <a-form-item :label="$t('product.auto_create_missing_tax_from_label_rate')">
                    <a-switch v-model:checked="autoCreateTaxes" />
                  </a-form-item>
                </a-space>
              </a-collapse-panel>
            </a-collapse>
          </a-form>
        </a-col>
      </a-row>

      <template #footer>
        <a-button type="primary" :loading="loading" @click="importItems">
          {{ $t("common.import") }}
        </a-button>
        <a-button key="back" @click="handleCancel">
          {{ $t("common.cancel") }}
        </a-button>
      </template>
    </a-modal>
  </div>
</template>

<script>
import { defineComponent, ref } from "vue";
import { CloudDownloadOutlined, UploadOutlined } from "@ant-design/icons-vue";
import { useI18n } from "vue-i18n";
import apiAdmin from "../../composable/apiAdmin";
import UploadFile from "./file/UploadFile.vue";
import { message, notification } from "ant-design-vue";
import common from "../../composable/common";

export default defineComponent({
  props: {
    pageTitle: String,
    sampleFileUrl: String,
    importUrl: { type: String, required: true },
    /**
     * Extra fields to append to FormData (e.g., { use_custom_field_master: '1' })
     */
    extraFields: { type: Object, default: () => ({}) },
    /**
     * Hide the options panel if you want a minimal dialog.
     */
    hideOptions: { type: Boolean, default: false },
  },
  emits: ["onUploadSuccess"],
  components: { CloudDownloadOutlined, UploadFile, UploadOutlined },
  setup(props, { emit }) {
    const { loading, rules } = apiAdmin();
    const { t } = useI18n();
    const { appSetting } = common();

    const fileList = ref([]);
    const visible = ref(false);

    // default toggles (keep or hide via hideOptions)
    const storeUnknownAsCustom = ref(true);
    const autoCreateMasters = ref(false);
    const autoCreateTaxes = ref(false);

    const beforeUpload = (file) => {
      fileList.value = [file]; // keep only one
      return false;
    };

    const importItems = async () => {
      const f = fileList.value?.[0];
      if (!f) {
        message.error(t("common.file") + " " + t("common.required"));
        return;
      }

      const blob = f.originFileObj || f;
      const fd = new FormData();
      fd.append("file", blob, f.name || "import.csv");

      // common flags (your backend may or may not use these)
      fd.append("store_unknown_as_custom", storeUnknownAsCustom.value ? "1" : "0");
      fd.append("auto_create_masters", autoCreateMasters.value ? "1" : "0");
      fd.append("auto_create_taxes", autoCreateTaxes.value ? "1" : "0");

      // NEW: forward any extra flags provided by parent (like use_custom_field_master=1)
      Object.entries(props.extraFields || {}).forEach(([k, v]) => {
        fd.append(k, v);
      });

      loading.value = true;
      try {
        const res = await axiosAdmin.post(props.importUrl, fd, {
          headers: { "Content-Type": "multipart/form-data" },
        });

        notification.success({
          placement: appSetting.value.rtl ? "bottomLeft" : "bottomRight",
          message: t("common.success"),
          description: t("messages.imported_successfully"),
        });

        // Optional: show a summary if backend returns it
        const summary = res?.data?.data?.summary || res?.data?.summary;
        if (summary) {
          const { inserted = 0, customSaved = 0, autoCreated = {} } = summary;
          const cats = (autoCreated.categories || []).join(", ");
          const brds = (autoCreated.brands || []).join(", ");
          const unts = (autoCreated.units || []).join(", ");
          const txes = (autoCreated.taxes || []).join(", ");
          const lines = [
            `${t("common.inserted")}: ${inserted}`,
            `${t("product.custom_fields_saved")}: ${customSaved}`,
          ];
          if (cats) lines.push(`${t("category.category")} +: ${cats}`);
          if (brds) lines.push(`${t("product.brand")} +: ${brds}`);
          if (unts) lines.push(`${t("unit.unit")} +: ${unts}`);
          if (txes) lines.push(`${t("tax.tax")} +: ${txes}`);

          notification.info({
            placement: appSetting.value.rtl ? "bottomLeft" : "bottomRight",
            message: t("common.summary"),
            description: lines.join("\n"),
            duration: 6,
          });
        }

        handleCancel();
        emit("onUploadSuccess");
      } catch (err) {
        const status = err?.response?.status;
        const data = err?.response?.data;
        const msg = data?.error?.message || data?.message || "Unknown error";
        // eslint-disable-next-line no-console
        console.error("IMPORT FAIL:", { status, data, err });
        message.error(msg);
      } finally {
        loading.value = false;
      }
    };

    const showModal = () => { visible.value = true; };
    const handleCancel = () => { fileList.value = []; visible.value = false; };

    return {
      fileList, visible, loading, rules,
      storeUnknownAsCustom, autoCreateMasters, autoCreateTaxes,
      beforeUpload, importItems, showModal, handleCancel,
    };
  },
});
</script>
