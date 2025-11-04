<!-- index.vue -->
<template>
  <AdminPageHeader>
    <template #header>
      <a-page-header :title="$t(`menu.expenses`)" class="p-0" />
    </template>
    <template #breadcrumb>
      <a-breadcrumb separator="-" style="font-size: 12px">
        <a-breadcrumb-item>
          <router-link :to="{ name: 'admin.dashboard.index' }">
            {{ $t(`menu.dashboard`) }}
          </router-link>
        </a-breadcrumb-item>
        <a-breadcrumb-item>
          {{ $t(`menu.expense_manager`) }}
        </a-breadcrumb-item>
        <a-breadcrumb-item>
          {{ $t(`menu.expenses`) }}
        </a-breadcrumb-item>
      </a-breadcrumb>
    </template>
  </AdminPageHeader>

  <admin-page-filters>
    <a-row :gutter="[16, 16]">
      <a-col :xs="24" :sm="24" :md="12" :lg="10" :xl="10">
        <a-space>
          <template
            v-if="permsArray.includes(`expenses_create`) || permsArray.includes('admin')"
          >
            <a-button type="primary" @click="addItem">
              <PlusOutlined />
              {{ $t('expense.add') }}
            </a-button>
          </template>

          <a-button
            v-if="
              table.selectedRowKeys.length > 0 &&
              (permsArray.includes('expenses_delete') || permsArray.includes('admin'))
            "
            type="primary"
            @click="showSelectedDeleteConfirm"
            danger
          >
            <template #icon><DeleteOutlined /></template>
            {{ $t('common.delete') }}
          </a-button>
        </a-space>
      </a-col>

      <a-col :xs="24" :sm="24" :md="12" :lg="14" :xl="14">
        <a-row :gutter="[16, 16]" justify="end">
          <!-- Expense Category -->
          <a-col :xs="24" :sm="24" :md="8" :lg="6" :xl="6">
            <a-select
              v-model:value="filters.expense_category_id"
              show-search
              style="width: 100%"
              :placeholder="$t('common.select_default_text', [$t('expense.expense_category')])"
              @change="reFetchDatatable"
              :allowClear="true"
              optionFilterProp="label"
            >
              <a-select-option
                v-for="expenseCategory in preFetchData.expenseCategories"
                :key="expenseCategory.xid"
                :value="expenseCategory.xid"
                :label="expenseCategory.name"
              >
                {{ expenseCategory.name }}
              </a-select-option>
            </a-select>
          </a-col>

          <!-- User -->
          <a-col :xs="24" :sm="24" :md="8" :lg="6" :xl="6">
            <a-select
              v-model:value="filters.user_id"
              show-search
              style="width: 100%"
              :placeholder="$t('common.select_default_text', [$t('expense.user')])"
              @change="reFetchDatatable"
              :allowClear="true"
              optionFilterProp="label"
            >
              <a-select-option
                v-for="staffMember in preFetchData.staffMembers"
                :key="staffMember.xid"
                :value="staffMember.xid"
                :label="staffMember.name"
              >
                {{ staffMember.name }}
              </a-select-option>
            </a-select>
          </a-col>

          <!-- ✅ Payment Mode filter (from API; sends hashed xid) -->
          <a-col :xs="24" :sm="24" :md="8" :lg="6" :xl="6">
            <a-select
              v-model:value="filters.payment_mode_id"
              show-search
              style="width: 100%"
              :placeholder="$t('common.select_default_text', [$t('expense.payment_mode')])"
              @change="reFetchDatatable"
              :allowClear="true"
              optionFilterProp="label"
            >
              <a-select-option
                v-for="m in paymentModes"
                :key="m.xid"
                :value="m.xid"
                :label="m.name"
              >
                {{ m.name }}
              </a-select-option>
            </a-select>
          </a-col>

          <!-- Date Range -->
          <a-col :xs="24" :sm="24" :md="8" :lg="8" :xl="8">
            <DateRangePicker
              @dateTimeChanged="(changedDateTime) => { extraFilters.dates = changedDateTime; reFetchDatatable(); }"
            />
          </a-col>
        </a-row>
      </a-col>
    </a-row>
  </admin-page-filters>

  <admin-page-table-content>
    <AddEdit
      :addEditType="addEditType"
      :visible="addEditVisible"
      :url="addEditUrl"
      @addEditSuccess="addEditSuccess"
      @closed="onCloseAddEdit"
      :formData="formData"
      :data="viewData"
      :pageTitle="pageTitle"
      :successMessage="successMessage"
    />

    <a-row class="mt-20">
      <a-col :span="24">
        <div class="table-responsive">
          <a-table
            :row-selection="{
              selectedRowKeys: table.selectedRowKeys,
              onChange: onRowSelectChange,
              getCheckboxProps: (record) => ({ disabled: false, name: record.xid }),
            }"
            :columns="columnsWithPaymentMode"
            :row-key="(record) => record.xid"
            :data-source="table.data"
            :pagination="table.pagination"
            :loading="table.loading"
            @change="handleTableChange"
            bordered
            size="middle"
          >
            <template #bodyCell="{ column, text, record }">
              <template v-if="column.dataIndex === 'expense_category_id'">
                {{ record.expense_category?.name }}
              </template>

              <template v-if="column.dataIndex === 'user_id'">
                {{ record.user?.name }}
              </template>

              <!-- ✅ Show Payment Mode name -->
              <template v-if="column.dataIndex === 'payment_mode_id'">
                {{ record.payment_mode?.name || '—' }}
              </template>

              <template v-if="column.dataIndex === 'amount'">
                {{ formatAmountCurrency(text) }}
              </template>

              <template v-if="column.dataIndex === 'date'">
                {{ formatDate(record.date) }}
              </template>

              <template v-if="column.dataIndex === 'action'">
                <a-button
                  v-if="permsArray.includes(`expenses_edit`) || permsArray.includes('admin')"
                  type="primary"
                  @click="editItem(record)"
                  style="margin-left: 4px"
                >
                  <template #icon><EditOutlined /></template>
                </a-button>
                <a-button
                  v-if="permsArray.includes(`expenses_delete`) || permsArray.includes('admin')"
                  type="primary"
                  @click="showDeleteConfirm(record.xid)"
                  style="margin-left: 4px"
                >
                  <template #icon><DeleteOutlined /></template>
                </a-button>
              </template>
            </template>

            <template #summary>
              <a-table-summary-row>
                <a-table-summary-cell :col-span="1"></a-table-summary-cell>
                <a-table-summary-cell :col-span="1">
                  <a-typography-text strong>{{ $t('common.total') }}</a-typography-text>
                </a-table-summary-cell>
                <a-table-summary-cell :col-span="1">
                  <a-typography-text strong>
                    {{ formatAmountCurrency(totals.totalAmount) }}
                  </a-typography-text>
                </a-table-summary-cell>
              </a-table-summary-row>
            </template>
          </a-table>
        </div>
      </a-col>
    </a-row>
  </admin-page-table-content>
</template>

<script>
import { onMounted, watch, ref, computed } from "vue";
import { PlusOutlined, EditOutlined, DeleteOutlined } from "@ant-design/icons-vue";
import AddEdit from "./AddEdit.vue";
import fields from "./fields";
import crud from "../../../../common/composable/crud";
import common from "../../../../common/composable/common";
import AdminPageHeader from "../../../../common/layouts/AdminPageHeader.vue";
import DateRangePicker from "../../../../common/components/common/calendar/DateRangePicker.vue";

export default {
  components: {
    PlusOutlined,
    EditOutlined,
    DeleteOutlined,
    AddEdit,
    AdminPageHeader,
    DateRangePicker,
  },
  setup() {
    const {
      url,
      addEditUrl,
      initData,
      columns,
      filters,
      preFetchData,
      getPreFetchData,
      hashableColumns,
    } = fields();

    const crudVariables = crud();
    const { appSetting, permsArray, formatDate, selectedWarehouse, formatAmountCurrency } = common();

    // ✅ Payment Modes from API (hashed xid list)
    const paymentModes = ref([]);

    // filters: use payment_mode_id (hashed) so backend filterable works with your Hash cast
    if (!("payment_mode_id" in filters)) {
      filters.payment_mode_id = null;
    }

    // Make sure table hash-decodes this filter key
    if (Array.isArray(crudVariables.hashableColumns.value)) {
      if (!crudVariables.hashableColumns.value.includes("payment_mode_id")) {
        crudVariables.hashableColumns.value.push("payment_mode_id");
      }
    }

    const extraFilters = ref({ dates: [] });

    onMounted(() => {
      // prefetch existing stuff
      getPreFetchData();

      // load payment modes list for filter
      // (axiosAdmin is available globally in your app)
      axiosAdmin.get("payment-modes", { params: { limit: 10000 } }).then(({ data }) => {
        paymentModes.value = Array.isArray(data) ? data : (data?.data ?? []);
      });

      crudVariables.crudUrl.value = addEditUrl;
      crudVariables.langKey.value = "expense";
      crudVariables.initData.value = { ...initData };
      crudVariables.formData.value = { ...initData };

      reFetchDatatable();
    });

    const reFetchDatatable = () => {
      crudVariables.tableUrl.value = {
        url,
        filters,      // includes filters.payment_mode_id (hashed)
        extraFilters,
      };
      crudVariables.fetch({ page: 1 });
    };

    // Inject a Payment Mode column into the existing columns (after user/amount)
    const columnsWithPaymentMode = computed(() => {
      const base = Array.isArray(columns.value) ? [...columns.value] : [];
      const exists = base.some(c => c.dataIndex === "payment_mode_id");
      if (!exists) {
        // find insert index (after user or after amount)
        let insertAt = base.findIndex(c => c.dataIndex === "amount");
        if (insertAt === -1) insertAt = Math.max(1, base.length - 2);

        base.splice(insertAt, 0, {
          title: "Payment Mode",
          dataIndex: "payment_mode_id",
          sorter: false,
        });
      }
      return base;
    });

    const totals = computed(() => {
      let totalAmount = 0;
      crudVariables.table.data.forEach((row) => {
        totalAmount += row.amount;
      });
      return { totalAmount };
    });

    watch(selectedWarehouse, () => {
      reFetchDatatable();
    });

    return {
      columnsWithPaymentMode,
      appSetting,
      formatDate,
      ...crudVariables,

      filters,
      extraFilters,
      preFetchData,
      reFetchDatatable,
      permsArray,
      formatAmountCurrency,
      totals,

      paymentModes,
    };
  },
};
</script>
