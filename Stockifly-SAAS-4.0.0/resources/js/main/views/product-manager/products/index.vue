<template>
  <AdminPageHeader>
    <template #header>
      <a-page-header :title="$t('menu.products')" class="p-0" />
    </template>
    <template #breadcrumb>
      <a-breadcrumb separator="-" style="font-size: 12px">
        <a-breadcrumb-item>
          <router-link :to="{ name: 'admin.dashboard.index' }">
            {{ $t('menu.dashboard') }}
          </router-link>
        </a-breadcrumb-item>
        <a-breadcrumb-item>
          {{ $t('menu.product_manager') }}
        </a-breadcrumb-item>
        <a-breadcrumb-item>
          {{ $t('menu.products') }}
        </a-breadcrumb-item>
      </a-breadcrumb>
    </template>
  </AdminPageHeader>

  <admin-page-filters>
    <a-row :gutter="[16,16]">
      <a-col :xs="24" :sm="24" :md="12" :lg="10" :xl="10">
        <a-space>
          <template v-if="permsArray.includes('products_create') || permsArray.includes('admin')">
            <SubscriptionModuleVisibility moduleName="product">
              <a-button type="primary" @click="() => { formData = { ...formData, product_type: productType }; addItem(); }">
                <PlusOutlined />
                {{ $t("product.add") }}
              </a-button>
            </SubscriptionModuleVisibility>

            <ImportProducts
              :pageTitle="$t('product.import_products')"
              :sampleFileUrl="sampleFileUrl"
              importUrl="products/import"
              :extraFields="{ store_unknown_as_custom: '1', use_custom_field_master: '0', auto_create_custom_fields: '0' }"
              @onUploadSuccess="setUrlData"
            />

            <ImportProducts
              :pageTitle="$t('product.import_products_using_custom_fields_master')"
              :sampleFileUrl="sampleFileUrl"
              importUrl="products/import"
              :extraFields="{ store_unknown_as_custom: '1', use_custom_field_master: '1', auto_create_custom_fields: '1' }"
              :hideOptions="true"
              @onUploadSuccess="setUrlData"
            />
          </template>

          <a-button
            v-if="table.selectedRowKeys.length > 0 && (permsArray.includes('products_delete') || permsArray.includes('admin'))"
            type="primary"
            @click="showSelectedDeleteConfirm"
            danger
          >
            <template #icon><DeleteOutlined /></template>
            {{ $t("common.delete") }}
          </a-button>
        </a-space>
      </a-col>

      <a-col :xs="24" :sm="24" :md="12" :lg="14" :xl="14">
        <a-row :gutter="[16,16]" justify="end">
          <a-col :xs="24" :sm="24" :md="8" :lg="8" :xl="6">
            <a-input-search
              v-model:value="table.searchString"
              show-search
              @change="onTableSearch"
              @search="onTableSearch"
              style="width:100%"
              :loading="table.filterLoading"
              :placeholder="$t('common.search')"
            />
          </a-col>

          <a-col :xs="24" :sm="24" :md="8" :lg="8" :xl="6">
            <a-select
              v-model:value="filters.warehouse_id"
              :placeholder="$t('common.select_default_text', [$t('warehouse.warehouse')])"
              allow-clear
              style="width: 100%"
              @change="setUrlData"
            >
              <a-select-option v-for="warehouse in warehouses" :key="warehouse.xid ?? 'all'" :value="warehouse.xid">
                {{ warehouse.name }}
              </a-select-option>
            </a-select>
          </a-col>

          <a-col :xs="24" :sm="24" :md="8" :lg="8" :xl="6">
            <a-select
              v-model:value="filters.brand_id"
              :placeholder="$t('common.select_default_text', [$t('product.brand')])"
              :allowClear="true"
              style="width: 100%"
              optionFilterProp="title"
              show-search
              @change="setUrlData"
            >
              <a-select-option v-for="brand in brands" :key="brand.xid" :title="brand.name" :value="brand.xid">
                {{ brand.name }}
              </a-select-option>
            </a-select>
          </a-col>

          <a-col :xs="24" :sm="24" :md="8" :lg="8" :xl="6">
            <a-tree-select
              v-model:value="filters.category_id"
              show-search
              style="width:100%"
              :dropdown-style="{ maxHeight:'250px', overflow:'auto' }"
              :placeholder="$t('common.select_default_text', [$t('category.category')])"
              :tree-data="categories"
              allow-clear
              tree-default-expand-all
              :filterTreeNode="filterTreeNode"
              @change="setUrlData"
            />
          </a-col>
        </a-row>
      </a-col>
    </a-row>
  </admin-page-filters>

  <admin-page-table-content>
    <!-- Add/Edit Product -->
    <AddEdit
      :addEditType="addEditType"
      :visible="addEditVisible"
      :url="addEditUrl"
      @addEditSuccess="addEditSuccess"
      @addAndNewSuccess="addAndNewSuccess"
      @addAndContinueSuccess="addAndContinueSuccess"
      @closed="onCloseAddEdit"
      :formData="formData"
      :data="viewData"
      :pageTitle="pageTitle"
      :successMessage="successMessage"
    />

    <ProductView :product="viewData" :visible="detailsVisible" @closed="onCloseDetails" />
    <ProductHistory :product="modelData" :visible="productDetailsVisible" @closed="onCloseProductDetails" />

    <SubscriptionModuleVisibilityMessage moduleName="product" />

    <!-- Product Type Tabs -->
    <a-row :gutter="16">
      <a-col :span="24">
        <a-tabs v-model:activeKey="productType" @change="setUrlData">
          <a-tab-pane key="single">
            <template #tab><span><AppstoreAddOutlined /> {{ $t('variations.single_type_product') }}</span></template>
          </a-tab-pane>
          <a-tab-pane key="variable">
            <template #tab><span><ClusterOutlined /> {{ $t('variations.variant_type_product') }}</span></template>
          </a-tab-pane>
          <a-tab-pane key="service">
            <template #tab><span><ClusterOutlined /> {{ $t('variations.service_type_product') }}</span></template>
          </a-tab-pane>
        </a-tabs>
      </a-col>
    </a-row>

    <!-- Product Table -->
    <a-row>
      <a-col :span="24">
        <div class="table-responsive">
          <a-table
            :row-selection="{ selectedRowKeys: table.selectedRowKeys, onChange: onRowSelectChange, getCheckboxProps: record => ({ disabled: false, name: record.xid }) }"
            :columns="columns"
            :row-key="record => record.xid"
            :data-source="filteredProducts"
            :pagination="table.pagination"
            :loading="table.loading"
            @change="handleTableChange"
            bordered
            size="middle"
          >
            <!-- Table body -->
            <template #bodyCell="{ column, record }">
              <!-- Status -->
              <template v-if="column.dataIndex==='status' && record?.details">
                <a-popover v-if="record.details.status==='out_of_stock'" placement="top">
                  <template #content>{{ $t('common.out_of_stock') }}</template>
                  <a-badge status="error" />
                </a-popover>
                <a-badge v-else status="success" />
              </template>

              <!-- Name -->
              <template v-if="column.dataIndex==='name'">
                <a-badge>
                  <a-avatar shape="square" :src="record.image_url" />
                  <a-typography-link @click="viewItem(record)">{{ record.name }}</a-typography-link>
                </a-badge>
              </template>

              <!-- Warehouse / Category / Brand -->
              <template v-if="column.dataIndex==='warehouse_name'">{{ record.details?.warehouse?.name || '-' }}</template>
              <template v-if="column.dataIndex==='category_id'">{{ record.category?.name || '-' }}</template>
              <template v-if="column.dataIndex==='brand_id'">{{ record.brand?.name || '-' }}</template>

              <!-- Prices -->
              <template v-if="column.dataIndex==='sales_price'">
                <span v-if="productType==='single'||productType==='service'">{{ formatAmountCurrency(record.details?.sales_price) }}</span>
                <span v-else-if="productType==='variable'">{{ getVariableProductSalePrice(record) }}</span>
              </template>
              <template v-if="column.dataIndex==='purchase_price'">
                <span v-if="productType==='single'">{{ formatAmountCurrency(record.details?.purchase_price) }}</span>
                <span v-else-if="productType==='variable'">{{ getVariableProductPurchasePrice(record) }}</span>
              </template>

              <!-- Stock -->
              <template v-if="column.dataIndex==='current_stock'">
                <a-typography-link v-if="productType==='single'" type="primary" @click="openProductDetails(record)">
                  {{ `${record.details?.current_stock} ${record.unit?.short_name || ''}` }}
                </a-typography-link>
                <a-typography-link v-else-if="productType==='variable'" type="primary" @click="openProductDetails(record)">
                  {{ getVariableProductStockSum(record) }}
                </a-typography-link>
              </template>

              <!-- Actions -->
              <template v-if="column.dataIndex==='action'">
                <a-space>
                  <a-button @click="viewItem(record)" type="primary"><template #icon><EyeOutlined /></template></a-button>
                  <a-button v-if="permsArray.includes('products_edit')||permsArray.includes('admin')" @click="editItem(record)" type="primary"><template #icon><EditOutlined /></template></a-button>
                  <a-button v-if="permsArray.includes('products_delete')||permsArray.includes('admin')" @click="showDeleteConfirm(record.xid)" type="primary" danger><template #icon><DeleteOutlined /></template></a-button>
                </a-space>
              </template>
            </template>

            <!-- Expanded Row for Variable Products -->
            <template v-if="productType==='variable'" #expandedRowRender="productData">
              <a-table
                v-if="productData?.record?.variations"
                :columns="variantColumns"
                :row-key="record=>record.xid"
                :data-source="productData.record.variations"
                :pagination="false"
                size="middle"
              >
                <template #bodyCell="{ column, record }">
                  <template v-if="column.dataIndex==='name'">{{ record.name }}</template>
                  <template v-if="column.dataIndex==='sales_price'">{{ formatAmountCurrency(record.details?.sales_price) }}</template>
                  <template v-if="column.dataIndex==='purchase_price'">{{ formatAmountCurrency(record.details?.purchase_price) }}</template>
                  <template v-if="column.dataIndex==='current_stock'">{{ `${record.details?.current_stock} ${productData.record.unit?.short_name || ''}` }}</template>
                  <template v-if="column.dataIndex==='action'"><a-button @click="viewItem(record)" type="links"><template #icon><EyeOutlined /></template></a-button></template>
                </template>
              </a-table>
            </template>

            <!-- Summary -->
            <template #summary v-if="productType!=='service'">
              <a-table-summary-row>
                <a-table-summary-cell :col-span="5"></a-table-summary-cell>
                <a-table-summary-cell v-if="productType==='variable'" :col-span="1"></a-table-summary-cell>
                <a-table-summary-cell :col-span="2"></a-table-summary-cell>
                <a-table-summary-cell :col-span="1"><a-typography-text strong>{{$t('common.total')}}</a-typography-text></a-table-summary-cell>
                <a-table-summary-cell :col-span="1"><a-typography-text strong>{{totals.totalCurrentStock}}</a-typography-text></a-table-summary-cell>
              </a-table-summary-row>
            </template>
          </a-table>
        </div>
      </a-col>
    </a-row>
  </admin-page-table-content>
</template>

<script>
import { ref, onMounted, computed, watch } from "vue";
import { useI18n } from "vue-i18n";
import { maxBy, minBy, sumBy } from "lodash-es";
import {
  EyeOutlined, PlusOutlined, EditOutlined, DeleteOutlined,
  ClusterOutlined, AppstoreAddOutlined
} from "@ant-design/icons-vue";
import crud from "../../../../common/composable/crud";
import common from "../../../../common/composable/common";
import fields from "./fields";
import AddEdit from "./AddEdit.vue";
import ProductView from "./View.vue";
import ProductHistory from "./productHistory.vue";
import AdminPageHeader from "../../../../common/layouts/AdminPageHeader.vue";
import ImportProducts from "../../../../common/core/ui/Import.vue";
import SubscriptionModuleVisibility from "../../../../common/components/common/visibility/SubscriptionModuleVisibility.vue";
import SubscriptionModuleVisibilityMessage from "../../../../common/components/common/visibility/SubscriptionModuleVisibilityMessage.vue";

export default {
  components: {
    EyeOutlined, PlusOutlined, EditOutlined, DeleteOutlined,
    ClusterOutlined, AppstoreAddOutlined, AddEdit, ProductView, ProductHistory,
    AdminPageHeader, ImportProducts, SubscriptionModuleVisibility, SubscriptionModuleVisibilityMessage
  },
  setup() {
    const { t } = useI18n();
    const { addEditUrl, initData, productType, columns, columnsNames, variantColumns, filterableColumns, hashableColumns, multiDimensalObjectColumns } = fields();
    const crudVariables = crud();
    const { appSetting, permsArray, formatAmountCurrency, getRecursiveCategories, filterTreeNode, selectedWarehouse } = common();

    const filters = ref({ category_id: undefined, brand_id: undefined, warehouse_id: null });
    const sampleFileUrl = window.config.product_sample_file;
    const categories = ref([]);
    const brands = ref([]);
    const warehouses = ref([]);
    const productDetailsVisible = ref(false);
    const modelData = ref("");

    const loadWarehouses = async () => {
      try {
        const { data } = await axiosAdmin.get("warehouses?fields=id,xid,name&per_page=200");
        warehouses.value = [{ xid: null, name: "All Warehouses" }, ...data];
      } catch (e) {
        warehouses.value = [{ xid: null, name: "All Warehouses" }];
      }
    };

    const getInitialData = () => {
      Promise.all([
        axiosAdmin.get("categories?limit=10000"),
        axiosAdmin.get("brands?limit=10000")
      ]).then(([categoriesResponse, brandsResponse]) => {
        categories.value = getRecursiveCategories(categoriesResponse);
        brands.value = brandsResponse.data;
      });
    };

    const openProductDetails = record => { productDetailsVisible.value = true; modelData.value = record; };
    const onCloseProductDetails = () => { productDetailsVisible.value = false; };

    const setUrlData = () => {
      let url = "";
      // Construct URL based on productType...
      if(productType.value==='single') { url = 'products?...'; }
      else if(productType.value==='variable'){ url='products?...'; }
      else { url='products?...'; }

      const extraFilters = { product_type: productType.value };
      if(filters.value.warehouse_id) extraFilters.warehouse_id = filters.value.warehouse_id;

      crudVariables.tableUrl.value = { url, filters, extraFilters };
      crudVariables.table.filterableColumns = filterableColumns;
      crudVariables.fetch({ page: 1 });
    };

    const getVariableProductSalePrice = (record) => {
      let priceString = "";
      const minRecord = minBy(record.variations, o => o.details.sales_price);
      const maxRecord = maxBy(record.variations, o => o.details.sales_price);
      if(minRecord) priceString += formatAmountCurrency(minRecord.details.sales_price);
      if(maxRecord) priceString += " - " + formatAmountCurrency(maxRecord.details.sales_price);
      return priceString;
    };
    const getVariableProductPurchasePrice = (record) => {
      let priceString = "";
      const minRecord = minBy(record.variations, o => o.details.purchase_price);
      const maxRecord = maxBy(record.variations, o => o.details.purchase_price);
      if(minRecord) priceString += formatAmountCurrency(minRecord.details.purchase_price);
      if(maxRecord) priceString += " - " + formatAmountCurrency(maxRecord.details.purchase_price);
      return priceString;
    };
    const getVariableProductStockSum = record => sumBy(record.variations, o => o.details.current_stock);

    const totals = computed(() => {
      let totalCurrentStock = 0;
      crudVariables.table.data.forEach(row => {
        if(productType.value==='variable' && row.variations) totalCurrentStock += getVariableProductStockSum(row);
        else if(productType.value==='single' && row.details?.current_stock) totalCurrentStock += row.details.current_stock;
      });
      return { totalCurrentStock };
    });

    const filteredProducts = computed(() => crudVariables.table.data.filter(p => {
      if(filters.value.warehouse_id && p.details?.warehouse?.xid!==filters.value.warehouse_id) return false;
      if(filters.value.brand_id && p.brand?.xid!==filters.value.brand_id) return false;
      if(filters.value.category_id && p.category?.xid!==filters.value.category_id) return false;
      return true;
    }));

    onMounted(() => { loadWarehouses(); getInitialData(); setUrlData(); });

    watch(selectedWarehouse, () => setUrlData());

    return {
      columns, variantColumns, appSetting, ...crudVariables, permsArray, formatAmountCurrency,
      categories, brands, filters, warehouses, filterTreeNode, setUrlData, sampleFileUrl, productType,
      getVariableProductSalePrice, getVariableProductPurchasePrice, getVariableProductStockSum,
      totals, productDetailsVisible, modelData, openProductDetails, onCloseProductDetails,
      filteredProducts
    };
  }
};
</script>

<style lang="less">
.ant-badge-status-dot { width: 8px; height: 8px; }
</style>
