<template>
  <a-drawer
    :title="pageTitle"
    :width="drawerWidth"
    :open="visible"
    :body-style="{ paddingBottom: '80px' }"
    :footer-style="{ textAlign: 'right' }"
    :maskClosable="false"
    @close="onClose"
  >
    <a-form layout="vertical">
      <a-tabs v-model:activeKey="activeKey">
        <!-- BASIC DETAILS -->
        <a-tab-pane key="basic_details">
          <template #tab>
            <span>
              <FileTextOutlined />
              {{ $t('warehouse.basic_details') }}
            </span>
          </template>

          <a-row :gutter="16">
            <a-col :xs="24" :sm="24" :md="24" :lg="24">
              <a-row :gutter="16">
                <a-col :xs="24" :sm="24" :md="12" :lg="12">
                  <a-form-item
                    :label="$t('warehouse.name')"
                    name="name"
                    :help="rules.name ? rules.name.message : null"
                    :validateStatus="rules.name ? 'error' : null"
                    class="required"
                  >
                    <a-input
                      v-model:value="formData.name"
                      :placeholder="$t('common.placeholder_default_text', [$t('warehouse.name')])"
                      @keyup="formData.slug = slugify($event.target.value)"
                    />
                  </a-form-item>
                </a-col>

                <a-col :xs="24" :sm="24" :md="12" :lg="12">
                  <a-form-item
                    :label="$t('warehouse.slug')"
                    name="slug"
                    :help="rules.slug ? rules.slug.message : null"
                    :validateStatus="rules.slug ? 'error' : null"
                    class="required"
                  >
                    <a-input
                      v-model:value="formData.slug"
                      :placeholder="$t('common.placeholder_default_text', [$t('warehouse.slug')])"
                    />
                  </a-form-item>
                </a-col>
              </a-row>

              <!-- PARENT WAREHOUSE -->
              <a-row :gutter="16">
                <a-col :xs="24" :sm="24" :md="12" :lg="12">
                  <a-form-item
                    :label="$t('Parent Warhouse')"
                    name="parent_warehouse_id"
                    :help="rules.parent_warehouse_id ? rules.parent_warehouse_id.message : null"
                    :validateStatus="rules.parent_warehouse_id ? 'error' : null"
                  >
                    <a-select
                      v-model:value="formData.parent_warehouse_id"
                      show-search
                      allowClear
                      :placeholder="$t('common.placeholder_default_text', [$t('Parent Warhouse')])"
                      :filterOption="false"
                      :loading="parentLoading"
                      style="width: 100%"
                      @search="onSearchParent"
                      @dropdownVisibleChange="onParentOpen"
                    >
                      <a-select-option :value="null">
                        {{ $t('Select Warehouse') }}
                      </a-select-option>

                      <a-select-option
                        v-for="opt in parentOptionsFiltered"
                        :key="opt.value"
                        :value="opt.value"
                      >
                        {{ opt.label }}
                      </a-select-option>
                    </a-select>
                  </a-form-item>
                </a-col>
              </a-row>
              <!-- /PARENT WAREHOUSE -->

              <a-row :gutter="16">
                <a-col :xs="24" :sm="24" :md="16" :lg="16">
                  <a-form-item
                    :label="$t('warehouse.email')"
                    name="email"
                    :help="rules.email ? rules.email.message : null"
                    :validateStatus="rules.email ? 'error' : null"
                    class="required"
                  >
                    <a-input
                      v-model:value="formData.email"
                      :placeholder="$t('common.placeholder_default_text', [$t('warehouse.email')])"
                    />
                  </a-form-item>
                </a-col>
                <a-col :xs="24" :sm="24" :md="8" :lg="8">
                  <a-form-item
                    :label="$t('warehouse.show_email_on_invoice')"
                    name="show_email_on_invoice"
                    :help="rules.show_email_on_invoice ? rules.show_email_on_invoice.message : null"
                    :validateStatus="rules.show_email_on_invoice ? 'error' : null"
                  >
                    <a-switch v-model:checked="formData.show_email_on_invoice" :checkedValue="1" :unCheckedValue="0" />
                  </a-form-item>
                </a-col>
              </a-row>

              <a-row :gutter="16">
                <a-col :xs="24" :sm="24" :md="16" :lg="16">
                  <a-form-item
                    :label="$t('warehouse.phone')"
                    name="phone"
                    :help="rules.phone ? rules.phone.message : null"
                    :validateStatus="rules.phone ? 'error' : null"
                    class="required"
                  >
                    <a-input
                      v-model:value="formData.phone"
                      :placeholder="$t('common.placeholder_default_text', [$t('warehouse.phone')])"
                    />
                  </a-form-item>
                </a-col>
                <a-col :xs="24" :sm="24" :md="8" :lg="8">
                  <a-form-item
                    :label="$t('warehouse.show_phone_on_invoice')"
                    name="show_phone_on_invoice"
                    :help="rules.show_phone_on_invoice ? rules.show_phone_on_invoice.message : null"
                    :validateStatus="rules.show_phone_on_invoice ? 'error' : null"
                  >
                    <a-switch v-model:checked="formData.show_phone_on_invoice" :checkedValue="1" :unCheckedValue="0" />
                  </a-form-item>
                </a-col>
              </a-row>            </a-col>
          </a-row>

          <a-row :gutter="16">
            <a-col :xs="24" :sm="24" :md="6" :lg="6">
              <a-row :gutter="16">
                <a-col :span="24">
                  <a-form-item
                    :label="$t('warehouse.logo')"
                    name="logo"
                    :help="rules.logo ? rules.logo.message : null"
                    :validateStatus="rules.logo ? 'error' : null"
                  >
                    <Upload
                      :formData="formData"
                      folder="warehouses"
                      imageField="logo"
                      @onFileUploaded="(file) => { formData.logo = file.file; formData.logo_url = file.file_url; }"
                    />
                  </a-form-item>
                </a-col>
              </a-row>
            </a-col>

            <a-col :xs="24" :sm="24" :md="6" :lg="6">
              <a-row :gutter="16">
                <a-col :span="24">
                  <a-form-item
                    :label="$t('warehouse.dark_logo')"
                    name="dark_logo"
                    :help="rules.dark_logo ? rules.dark_logo.message : null"
                    :validateStatus="rules.dark_logo ? 'error' : null"
                  >
                    <Upload
                      :formData="formData"
                      folder="warehouses"
                      imageField="dark_logo"
                      @onFileUploaded="(file) => { formData.dark_logo = file.file; formData.dark_logo_url = file.file_url; }"
                    />
                  </a-form-item>
                </a-col>
              </a-row>
            </a-col>
          </a-row>

          <a-row :gutter="16">
            <a-col :xs="24" :sm="24" :md="24" :lg="24">
              <a-form-item
                :label="$t('warehouse.address')"
                name="address"
                :help="rules.address ? rules.address.message : null"
                :validateStatus="rules.address ? 'error' : null"
              >
                <a-textarea
                  v-model:value="formData.address"
                  :placeholder="$t('common.placeholder_default_text', [$t('warehouse.address')])"
                  :auto-size="{ minRows: 2, maxRows: 3 }"
                />
              </a-form-item>
            </a-col>
          </a-row>

          <a-row :gutter="16">
            <a-col :xs="24" :sm="24" :md="24" :lg="24">
              <a-form-item>
                <a-typography-paragraph type="warning" strong>
                  <blockquote>{{ $t('warehouse.details_will_be_shown_on_invoice') }}</blockquote>
                </a-typography-paragraph>
              </a-form-item>
            </a-col>
          </a-row>

          <a-row :gutter="16">
            <a-col :xs="24" :sm="24" :md="24" :lg="24">
              <a-form-item
                :label="$t('warehouse.bank_details')"
                name="bank_details"
                :help="rules.bank_details ? rules.bank_details.message : null"
                :validateStatus="rules.bank_details ? 'error' : null"
              >
                <a-textarea
                  v-model:value="formData.bank_details"
                  :placeholder="$t('common.placeholder_default_text', [$t('warehouse.bank_details')])"
                  :auto-size="{ minRows: 2, maxRows: 3 }"
                />
              </a-form-item>
            </a-col>
          </a-row>

          <a-row :gutter="16">
            <a-col :xs="24" :sm="24" :md="24" :lg="24">
              <a-form-item
                :label="$t('warehouse.terms_condition')"
                name="terms_condition"
                :help="rules.terms_condition ? rules.terms_condition.message : null"
                :validateStatus="rules.terms_condition ? 'error' : null"
              >
                <a-textarea
                  v-model:value="formData.terms_condition"
                  :placeholder="$t('common.placeholder_default_text', [$t('warehouse.terms_condition')])"
                  :auto-size="{ minRows: 2, maxRows: 3 }"
                />
              </a-form-item>
            </a-col>
          </a-row>

          <a-row :gutter="16">
            <a-col :span="24">
              <a-form-item
                :label="$t('warehouse.signature')"
                name="signature"
                :help="rules.signature ? rules.signature.message : null"
                :validateStatus="rules.signature ? 'error' : null"
              >
                <Upload
                  :formData="formData"
                  folder="warehouses"
                  imageField="signature"
                  @onFileUploaded="(file) => { formData.signature = file.file; formData.signature_url = file.file_url; }"
                />
              </a-form-item>
            </a-col>
          </a-row>
        </a-tab-pane>

        <!-- ===================== VISIBILITY ===================== -->
        <a-tab-pane key="visibility">
          <template #tab>
            <span>
              <EyeOutlined />
              {{ $t('warehouse.visibility') }}
            </span>
          </template>

          <a-row :gutter="16">
            <a-col :xs="24" :sm="24" :md="24" :lg="24">
              <a-form-item
                :label="$t('warehouse.customers_visibility')"
                name="customers_visibility"
                :help="rules.customers_visibility ? rules.customers_visibility.message : null"
                :validateStatus="rules.customers_visibility ? 'error' : null"
              >
                <a-radio-group v-model:value="formData.customers_visibility">
                  <a-radio :style="radioStyle" value="all">{{ $t('warehouse.view_all_customers') }}</a-radio>
                  <a-radio :style="radioStyle" value="warehouse">{{ $t('warehouse.view_warehouse_customers') }}</a-radio>
                </a-radio-group>
              </a-form-item>
            </a-col>
          </a-row>

          <a-row :gutter="16">
            <a-col :xs="24" :sm="24" :md="24" :lg="24">
              <a-form-item
                :label="$t('warehouse.suppliers_visibility')"
                name="suppliers_visibility"
                :help="rules.suppliers_visibility ? rules.suppliers_visibility.message : null"
                :validateStatus="rules.suppliers_visibility ? 'error' : null"
              >
                <a-radio-group v-model:value="formData.suppliers_visibility">
                  <a-radio :style="radioStyle" value="all">{{ $t('warehouse.view_all_suppliers') }}</a-radio>
                  <a-radio :style="radioStyle" value="warehouse">{{ $t('warehouse.view_warehouse_suppliers') }}</a-radio>
                </a-radio-group>
              </a-form-item>
            </a-col>
          </a-row>

          <a-row :gutter="16">
            <a-col :xs="24" :sm="24" :md="24" :lg="24">
              <a-form-item
                :label="$t('warehouse.products_visibility')"
                name="products_visibility"
                :help="rules.products_visibility ? rules.products_visibility.message : null"
                :validateStatus="rules.products_visibility ? 'error' : null"
              >
                <a-radio-group v-model:value="formData.products_visibility">
                  <a-radio :style="radioStyle" value="all">{{ $t('warehouse.view_all_products') }}</a-radio>
                  <a-radio :style="radioStyle" value="warehouse">{{ $t('warehouse.view_warehouse_products') }}</a-radio>
                </a-radio-group>
              </a-form-item>
            </a-col>
          </a-row>
        </a-tab-pane>

        <!-- ===================== POS SETTINGS ===================== -->
        <a-tab-pane key="pos_settings">
          <template #tab>
            <span>
              <SettingOutlined />
              {{ $t('menu.pos_settings') }}
            </span>
          </template>

          <a-row :gutter="16">
            <a-col :xs="24" :sm="24" :md="24" :lg="24">
              <a-form-item
                :label="$t('warehouse.default_pos_order_status')"
                name="default_pos_order_status"
                :help="rules.default_pos_order_status ? rules.default_pos_order_status.message : null"
                :validateStatus="rules.default_pos_order_status ? 'error' : null"
              >
                <a-select
                  v-model:value="formData.default_pos_order_status"
                  :placeholder="$t('warehouse.default_pos_order_status')"
                  style="width: 100%"
                >
                  <a-select-option
                    v-for="orderStatus in salesOrderStatus"
                    :key="orderStatus.key"
                    :value="orderStatus.key"
                  >
                    {{ orderStatus.value }}
                  </a-select-option>
                </a-select>
              </a-form-item>
            </a-col>
          </a-row>

          <a-row :gutter="16">
            <a-col :xs="24" :sm="24" :md="12" :lg="12">
              <a-form-item
                :label="$t('warehouse.barcode_type')"
                name="barcode_type"
                :help="rules.barcode_type ? rules.barcode_type.message : null"
                :validateStatus="rules.barcode_type ? 'error' : null"
                class="required"
              >
                <a-select v-model:value="formData.barcode_type" style="width: 100%">
                  <a-select-option value="barcode">{{ $t('warehouse.barcode') }}</a-select-option>
                  <a-select-option value="qrcode">{{ $t('warehouse.qrcode') }}</a-select-option>
                </a-select>
              </a-form-item>
            </a-col>
          </a-row>
        </a-tab-pane>
      </a-tabs>
    </a-form>

    <template #footer>
      <a-button type="primary" @click="onSubmit" style="margin-right: 8px" :loading="loading">
        <template #icon><SaveOutlined /></template>
        {{ addEditType == 'add' ? $t('common.create') : $t('common.update') }}
      </a-button>
      <a-button @click="onClose">{{ $t('common.cancel') }}</a-button>
    </template>
  </a-drawer>
</template>

<script>
import { defineComponent, ref, reactive, computed, onMounted, watch } from 'vue';
import {
  PlusOutlined,
  LoadingOutlined,
  SaveOutlined,
  FileTextOutlined,
  EyeOutlined,
  SettingOutlined,
} from '@ant-design/icons-vue';
import apiAdmin from '../../../../common/composable/apiAdmin.js';

import Upload from '../../../../common/core/ui/file/Upload.vue';
import common from '../../../../common/composable/common';
import { useStore } from 'vuex';

export default defineComponent({
  props: ['formData', 'data', 'visible', 'url', 'addEditType', 'pageTitle', 'successMessage'],
  emits: ['addEditSuccess', 'closed'],
  components: {
    PlusOutlined,
    LoadingOutlined,
    SaveOutlined,
    FileTextOutlined,
    EyeOutlined,
    SettingOutlined,
    Upload,
  },
  setup(props, { emit }) {
    const store = useStore();
    const { addEditRequestAdmin, loading, rules } = apiAdmin();
    const { slugify, salesOrderStatus, selectedWarehouse } = common();

    const activeKey = ref('basic_details');
    const radioStyle = reactive({ display: 'flex', height: '30px', lineHeight: '30px' });

    // ---------- Parent warehouse (from /warehouses) ----------
    const parentLoading = ref(false);
    const allWarehouses = ref([]);     // raw list from API
    const searchTerm = ref('');        // local search text

    // Make sure parent_id exists on the form
    if (props.formData && typeof props.formData.parent_id === 'undefined') {
      props.formData.parent_id = null;
    }

    const currentXid = computed(() => props.formData?.xid || null);

    // Turn warehouses into {value,label} and filter by search + exclude self
    const parentOptionsFiltered = computed(() => {
      const needle = (searchTerm.value || '').toLowerCase();
      return allWarehouses.value
        .filter(w => !currentXid.value || w.xid !== currentXid.value)
        .filter(w => !needle || (w.name || '').toLowerCase().includes(needle))
        .map(w => ({ value: w.xid, label: w.name }));
    });

    // Load from existing index: /warehouses (company-scoped)
   const loadParentList = async () => {
  parentLoading.value = true;
  try {
    const perPage = 200; // adjust if you expect more
    let page = 1;
    let results = [];
    // Some backends return { data: { data: [], meta, links } }
    // others use { data: [], meta, links }. Handle both.
    // We’ll loop until there’s no next page.

    // helper to extract rows/meta/links regardless of envelope
    const extract = (resp) => {
      const root = resp?.data ?? {};
      const body = root.data ?? root; // either {data:{...}} or {...}
      const rows = body.data ?? body; // either {data:[...]} or [...]
      const meta = body.meta ?? root.meta ?? null;
      const links = body.links ?? root.links ?? null;
      return { rows, meta, links };
    };

    // fetch pages
    /* eslint-disable no-constant-condition */
    while (true) {
      const { data } = await axiosAdmin.get('warehouses', {
        params: {
          per_page: perPage,
          page,
          fields: 'xid,name',   // ask only what we need
          order: 'name',        // optional; remove if your API rejects it
        },
      });

      const { rows, meta, links } = extract({ data });

      // normalize to {xid,name}
      const mapped = (Array.isArray(rows) ? rows : []).map(r => ({
        xid: r.xid ?? r.x_id ?? r.id,
        name: r.name ?? '',
      }));

      results = results.concat(mapped);

      // stop when no next page
      const hasNext =
        (meta && meta.current_page && meta.current_page < meta.last_page) ||
        (links && links.next);

      if (!hasNext) break;
      page += 1;
    }

    allWarehouses.value = results;
  } catch (e) {
    // Surface the server message to help debugging
    const serverMsg = e?.response?.data?.error?.message || e?.message || 'Unknown error';
    console.error('Failed to load warehouses:', serverMsg, e?.response?.data || e);
    allWarehouses.value = [];
  } finally {
    parentLoading.value = false;
  }
};


    const onSearchParent = (val) => {
      searchTerm.value = val || '';
      // local filter; no extra API call
    };

    const onParentOpen = (isOpen) => {
      if (isOpen && allWarehouses.value.length === 0) {
        loadParentList();
      }
    };

    // Prefill parent_id on edit with hashed id from API response
    const initParentField = () => {
      if (!props.formData.parent_id && props.data?.x_parent_id) {
        props.formData.parent_id = props.data.x_parent_id;
      }
    };

    watch(() => props.visible, (isOpen) => {
      if (isOpen) {
        initParentField();
        loadParentList();
      }
    });

    onMounted(() => {
      loadParentList();
    });
    // ---------------------------------------------------------

    const onSubmit = () => {
      addEditRequestAdmin({
        url: props.url,
        data: props.formData,
        successMessage: props.successMessage,
        success: (res) => {
          store.dispatch('auth/updateAllWarehouses');

          if (selectedWarehouse.value && selectedWarehouse.value.xid && selectedWarehouse.value.xid == res.xid) {
            axiosAdmin.post('change-warehouse', { warehouse_id: res.xid }).then((response) => {
              store.commit('auth/updateWarehouse', response.data.warehouse);
            });
          }

          emit('addEditSuccess', res.xid);
          activeKey.value = 'basic_details';
          rules.value = {};
        },
      });
    };

    const onClose = () => {
      activeKey.value = 'basic_details';
      rules.value = {};
      emit('closed');
    };

    return {
      loading,
      rules,
      onClose,
      onSubmit,
      slugify,
      activeKey,
      salesOrderStatus,
      radioStyle,
      drawerWidth: window.innerWidth <= 991 ? '90%' : '45%',

      // parent bindings
      parentLoading,
      parentOptionsFiltered,
      onSearchParent,
      onParentOpen,
      formData: props.formData,
    };
  },
});
</script>
