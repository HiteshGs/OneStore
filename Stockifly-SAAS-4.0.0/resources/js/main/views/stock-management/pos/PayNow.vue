<template>
  <a-drawer
    :title="$t('payments.order_payment')"
    :width="drawerWidth"
    :maskClosable="false"
    :open="visible"
    @close="drawerClosed"
  >
    <a-row>
      <!-- Left summary -->
      <a-col :xs="24" :sm="24" :md="8" :lg="8">
        <a-row>
          <a-col :span="24">
            <a-statistic
              :title="$t('stock.total_items')"
              :value="selectedProducts.length"
            />
          </a-col>

          <a-col :span="24" class="mt-20">
            <a-statistic
              :title="$t('stock.paying_amount')"
              :value="formatAmountCurrency(totalEnteredAmount)"
            />
          </a-col>

          <a-col :span="24" class="mt-20">
            <a-statistic
              :title="$t('stock.payable_amount')"
              :value="formatAmountCurrency(data.subtotal)"
            />
          </a-col>

          <a-col :span="24" class="mt-20">
            <a-statistic
              v-if="totalEnteredAmount <= data.subtotal"
              :title="$t('payments.due_amount')"
              :value="formatAmountCurrency(data.subtotal - totalEnteredAmount)"
            />
            <a-statistic
              v-else
              :title="$t('stock.change_return')"
              :value="formatAmountCurrency(totalEnteredAmount - data.subtotal)"
            />
          </a-col>
        </a-row>
      </a-col>

      <!-- Right content -->
      <a-col :xs="24" :sm="24" :md="16" :lg="16">
        <a-row :gutter="[24, 24]">
          <!-- Top actions -->
          <a-col :span="24" v-if="!showAddForm">
            <a-row :gutter="[16, 8]" class="mt-20">
              <a-col :md="10">
                <a-button block type="primary" @click="() => (showAddForm = true)">
                  <PlusOutlined /> {{ $t('payments.add') }}
                </a-button>
              </a-col>

              <a-col :md="10">
                <a-button
                  :loading="loading"
                  block
                  @click="openNameDialog"
                >
                  {{ $t('stock.complete_order') }} <RightOutlined />
                </a-button>
              </a-col>
            </a-row>
          </a-col>

          <!-- Back button -->
          <a-col :span="24" v-else>
            <a-button block type="primary" @click="goBack">
              <LeftOutlined /> {{ $t('common.back') }}
            </a-button>
          </a-col>

          <!-- Payments table -->
          <a-col :span="24" v-if="!showAddForm">
            <a-table
              :dataSource="allPaymentRecords"
              :columns="paymentRecordsColumns"
              :pagination="false"
              :rowKey="record => record.id"
            >
              <template #bodyCell="{ column, record }">
                <template v-if="column.dataIndex === 'payment_mode'">
                  {{ getPaymentModeName(record.payment_mode_id) }}
                </template>

                <template v-if="column.dataIndex === 'amount'">
                  {{ formatAmountCurrency(record.amount) }}
                </template>

                <template v-if="column.dataIndex === 'action'">
                  <a-button danger type="primary" @click="deletePayment(record.id)">
                    <DeleteOutlined />
                  </a-button>
                </template>
              </template>
            </a-table>
          </a-col>

          <!-- Add payment form -->
          <a-col :span="24" v-else>
            <a-form layout="vertical">
              <a-row :gutter="16">
                <a-col :md="12">
                  <a-form-item :label="$t('payments.payment_mode')">
                    <a-select v-model:value="formData.payment_mode_id" allowClear>
                      <a-select-option
                        v-for="m in paymentModes"
                        :key="m.xid"
                        :value="m.xid"
                      >
                        {{ m.name }}
                      </a-select-option>
                    </a-select>
                  </a-form-item>
                </a-col>

                <a-col :md="12">
                  <a-form-item :label="$t('stock.paying_amount')">
                    <a-input
                      v-model:value="formData.amount"
                      :prefix="appSetting.currency.symbol"
                    />
                  </a-form-item>
                </a-col>
              </a-row>

              <a-form-item :label="$t('payments.notes')">
                <a-textarea v-model:value="formData.notes" rows="4" />
              </a-form-item>

              <a-button block type="primary" @click="onSubmit" :loading="loading">
                <CheckOutlined /> {{ $t('common.add') }}
              </a-button>
            </a-form>
          </a-col>
        </a-row>
      </a-col>
    </a-row>

    <!-- Customer Name Modal -->
    <a-modal
      :open="nameModalVisible"
      title="Enter Customer Name"
      @ok="confirmNameAndProceed"
      @cancel="() => (nameModalVisible = false)"
      :maskClosable="false"
    >
      <a-input
        v-model:value="customerName"
        placeholder="Customer name (optional)"
      />
    </a-modal>

    <!-- Print chooser -->
    <a-modal
      :open="printModalVisible"
      title="Select Print Layout"
      @ok="completeOrderAndEmitPrint"
      @cancel="() => (printModalVisible = false)"
      :confirmLoading="loading"
      okText="Complete & Print"
      :maskClosable="false"
    >
      <a-radio-group v-model:value="selectedPrintSize">
        <a-radio value="A4">A4</a-radio>
        <a-radio value="A5">A5</a-radio>
        <a-radio value="80mm">Thermal 80mm</a-radio>
        <a-radio value="58mm">Thermal 58mm</a-radio>
      </a-radio-group>

      <div class="mt-20">
        <a-switch v-model:checked="autoOpenPrint" />
        <span class="ml-10">Print Preview</span>
      </div>
    </a-modal>
  </a-drawer>
</template>

<script>
import { ref, computed, onMounted } from "vue";
import {
  CheckOutlined,
  PlusOutlined,
  LeftOutlined,
  RightOutlined,
  DeleteOutlined,
} from "@ant-design/icons-vue";
import { useI18n } from "vue-i18n";
import { find, filter, sumBy } from "lodash-es";
import common from "../../../../common/composable/common";
import apiAdmin from "../../../../common/composable/apiAdmin";

export default {
  props: ["visible", "data", "selectedProducts", "successMessage"],
  emits: ["closed", "success", "print"],
  components: {
    CheckOutlined,
    PlusOutlined,
    LeftOutlined,
    RightOutlined,
    DeleteOutlined,
  },
  setup(props, { emit }) {
    const { addEditRequestAdmin, loading } = apiAdmin();
    const { appSetting, formatAmountCurrency } = common();
    const { t } = useI18n();

    const paymentModes = ref([]);
    const allPaymentRecords = ref([]);
    const showAddForm = ref(false);

    const formData = ref({
      payment_mode_id: undefined,
      amount: 0,
      notes: "",
    });

    const nameModalVisible = ref(false);
    const customerName = ref("");

    const printModalVisible = ref(false);
    const selectedPrintSize = ref("A4");
    const autoOpenPrint = ref(true);

    const paymentRecordsColumns = [
      { title: t("payments.payment_mode"), dataIndex: "payment_mode" },
      { title: t("payments.amount"), dataIndex: "amount" },
      { title: t("common.action"), dataIndex: "action" },
    ];

    onMounted(() => {
      axiosAdmin.get("payment-modes").then(res => {
        paymentModes.value = res.data;
      });
    });

    const openNameDialog = () => {
      nameModalVisible.value = true;
    };

    const confirmNameAndProceed = () => {
      nameModalVisible.value = false;
      printModalVisible.value = true;
    };

    const onSubmit = () => {
      allPaymentRecords.value.push({
        ...formData.value,
        id: Math.random().toString(36).slice(2),
      });
      formData.value = { payment_mode_id: undefined, amount: 0, notes: "" };
      showAddForm.value = false;
    };

    const completeOrderAndEmitPrint = () => {
      addEditRequestAdmin({
        url: "pos/save",
        data: {
          all_payments: allPaymentRecords.value,
          product_items: props.selectedProducts,
          details: props.data,
          customer_name: customerName.value || "",
          print_pref: { size: selectedPrintSize.value },
        },
        success: res => {
          emit("success", res.order);
          emit("print", {
            order: res.order,
            size: selectedPrintSize.value,
            autoOpen: autoOpenPrint.value,
          });
          drawerClosed();
        },
      });
    };

    const drawerClosed = () => {
      allPaymentRecords.value = [];
      customerName.value = "";
      showAddForm.value = false;
      printModalVisible.value = false;
      nameModalVisible.value = false;
      emit("closed");
    };

    const goBack = () => {
      showAddForm.value = false;
    };

    const getPaymentModeName = id => {
      const m = find(paymentModes.value, ["xid", id]);
      return m ? m.name : "-";
    };

    const deletePayment = id => {
      allPaymentRecords.value = filter(allPaymentRecords.value, p => p.id !== id);
    };

    const totalEnteredAmount = computed(() =>
      sumBy(allPaymentRecords.value, p => Number(p.amount || 0))
    );

    return {
      loading,
      paymentModes,
      allPaymentRecords,
      paymentRecordsColumns,
      showAddForm,
      formData,
      nameModalVisible,
      customerName,
      printModalVisible,
      selectedPrintSize,
      autoOpenPrint,
      openNameDialog,
      confirmNameAndProceed,
      onSubmit,
      completeOrderAndEmitPrint,
      drawerClosed,
      goBack,
      getPaymentModeName,
      deletePayment,
      appSetting,
      formatAmountCurrency,
      totalEnteredAmount,
      drawerWidth: window.innerWidth <= 991 ? "90%" : "50%",
    };
  },
};
</script>

<style>
.mt-10 { margin-top: 10px; }
.mt-20 { margin-top: 20px; }
.ml-10 { margin-left: 10px; }
</style>
