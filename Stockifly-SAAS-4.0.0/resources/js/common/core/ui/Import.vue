<template>
    <div>
        <a-button type="primary" @click="showModal">
            <CloudDownloadOutlined />
            {{ pageTitle }}
        </a-button>
        <a-modal v-model:open="visible" :title="pageTitle">
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

                        <!-- OPTIONAL: toggle. You can remove this block if you don’t want UI control -->
                        <a-form-item :label="$t('Store unknown columns as custom fields')">
                            <a-switch v-model:checked="storeUnknownAsCustom" />
                        </a-form-item>
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

export default defineComponent({
    props: ["pageTitle", "sampleFileUrl", "importUrl"],
    emits: ["onUploadSuccess"],
    components: { CloudDownloadOutlined, UploadFile, UploadOutlined },
    setup(props, { emit }) {
        const { addEditFileRequestAdmin, loading, rules } = apiAdmin();
        const { t } = useI18n();
        const fileList = ref([]);
        const visible = ref(false);
        const storeUnknownAsCustom = ref(true); // optional

        const beforeUpload = (file) => {
            fileList.value = [file]; // keep only the latest one
            return false;
        };

       const importItems = async () => {
  const f = fileList.value?.[0];
  if (!f) {
    message.error(t("common.file") + " " + t("common.required"));
    return;
  }

  const blob = f.originFileObj || f;              // Ant Design Upload stores real file in originFileObj
  const fd = new FormData();
  fd.append("file", blob, f.name || "import.csv"); // include filename!
  fd.append(
    "store_unknown_as_custom",
    storeUnknownAsCustom.value ? "1" : "0"
  );

  // Debug: verify what you're sending
  // eslint-disable-next-line no-console
  console.log("IMPORT DEBUG (direct) ->", {
    name: f.name,
    size: blob.size,
    type: blob.type,
    store_unknown_as_custom: storeUnknownAsCustom.value ? "1" : "0",
  });

  loading.value = true;
  try {
    await axiosAdmin.post(props.importUrl, fd, {
      headers: { "Content-Type": "multipart/form-data" },
    });

    notification.success({
      placement: appSetting.value.rtl ? "bottomLeft" : "bottomRight",
      message: t("common.success"),
      description: t("messages.imported_successfully"),
    });

    handleCancel();
    emit("onUploadSuccess");
  } catch (err) {
    const status = err?.response?.status;
    const data = err?.response?.data;
    const msg = data?.error?.message || data?.message || "Unknown error";

    // eslint-disable-next-line no-console
    console.error("IMPORT DEBUG FAIL (direct):", { status, data, err });

    message.error(msg);
  } finally {
    loading.value = false;
  }
};


        const showModal = () => { visible.value = true; };
        const handleCancel = () => { fileList.value = []; visible.value = false; };

        return {
            fileList, rules, loading,
            visible, showModal, handleCancel, importItems,
            beforeUpload, storeUnknownAsCustom
        };
    },
});
</script>