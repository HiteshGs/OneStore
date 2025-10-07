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
              style="margin-right: 50px"
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
              <a-col :xs="24" :sm="24" :md="10" :lg="10">
                <a-button :block="true" type="primary" @click="() => (showAddForm = true)">
                  <PlusOutlined /> {{ $t('payments.add') }}
                </a-button>
              </a-col>

              <a-col :xs="24" :sm="24" :md="10" :lg="10">
                <a-button :loading="loading" :block="true" @click="openPrintChooser">
                  {{ $t('stock.complete_order') }} <RightOutlined />
                </a-button>
              </a-col>
            </a-row>
          </a-col>

          <!-- Back button when add form is open -->
          <a-col :span="24" v-else>
            <a-row>
              <a-col :xs="24" :sm="24" :md="10" :lg="10">
                <a-button :block="true" type="primary" @click="goBack">
                  <LeftOutlined /> {{ $t('common.back') }}
                </a-button>
              </a-col>
            </a-row>
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
                  <a-button type="primary" @click="deletePayment(record.id)" danger>
                    <template #icon><DeleteOutlined /></template>
                  </a-button>
                </template>
              </template>
            </a-table>
          </a-col>

          <!-- Add payment form -->
          <a-col :span="24" v-else>
            <a-form layout="vertical">
              <a-row :gutter="16">
                <a-col :xs="24" :sm="24" :md="12" :lg="12">
                  <a-form-item
                    :label="$t('payments.payment_mode')"
                    name="payment_mode_id"
                    :help="rules.payment_mode_id ? rules.payment_mode_id.message : null"
                    :validateStatus="rules.payment_mode_id ? 'error' : null"
                  >
                    <a-select
                      v-model:value="formData.payment_mode_id"
                      :placeholder="$t('common.select_default_text', [$t('payments.payment_mode')])"
                      :allowClear="true"
                    >
                      <a-select-option
                        v-for="paymentMode in paymentModes"
                        :key="paymentMode.xid"
                        :value="paymentMode.xid"
                      >
                        {{ paymentMode.name }}
                      </a-select-option>
                    </a-select>
                  </a-form-item>
                </a-col>

                <a-col :xs="24" :sm="24" :md="12" :lg="12">
                  <a-form-item
                    :label="$t('stock.paying_amount')"
                    name="amount"
                    :help="rules.amount ? rules.amount.message : null"
                    :validateStatus="rules.amount ? 'error' : null"
                  >
                    <a-input
                      :prefix="appSetting.currency.symbol"
                      v-model:value="formData.amount"
                      :placeholder="$t('common.placeholder_default_text', [$t('stock.payable_amount')])"
                    />
                    <small style="color:#7c8db5 !important">
                      {{ $t('stock.payable_amount') }}
                      <span>{{ formatAmountCurrency(data.subtotal) }}</span>
                    </small>
                  </a-form-item>
                </a-col>
              </a-row>

              <a-row :gutter="16">
                <a-col :xs="24" :sm="24" :md="24" :lg="24">
                  <a-form-item
                    :label="$t('payments.notes')"
                    name="notes"
                    :help="rules.notes ? rules.notes.message : null"
                    :validateStatus="rules.notes ? 'error' : null"
                  >
                    <a-textarea
                      v-model:value="formData.notes"
                      :placeholder="$t('payments.notes')"
                      :rows="5"
                    />
                  </a-form-item>
                </a-col>
              </a-row>

              <a-row :gutter="16">
                <a-col :xs="24" :sm="24" :md="24" :lg="24">
                  <a-button type="primary" :loading="loading" @click="onSubmit" block>
                    <template #icon><CheckOutlined /></template>
                    {{ $t('common.add') }}
                  </a-button>
                </a-col>
              </a-row>
            </a-form>
          </a-col>
        </a-row>
      </a-col>
    </a-row>

    <!-- Print chooser modal -->
    <a-modal
      :open="printModalVisible"
      :title="$t('payments.select_print_layout')"
      @ok="completeOrderAndEmitPrint"
      @cancel="() => (printModalVisible = false)"
      :confirmLoading="loading"
      okText="Complete & Print"
      :maskClosable="false"
    >
      <a-radio-group v-model:value="selectedPrintSize">
        <a-radio value="A4">A4 (Invoice)</a-radio>
        <a-radio value="A5">A5</a-radio>
        <a-radio value="80mm">Thermal 80mm</a-radio>
        <a-radio value="58mm">Thermal 58mm</a-radio>
      </a-radio-group>

      <div class="mt-20">
        <a-switch v-model:checked="autoOpenPrint" />
        <span class="ml-10">{{ $t('payments.open_print_preview') }}</span>
      </div>

      <small class="block mt-10" style="color:#7c8db5">
        {{ $t('payments.you_can_change_later') }}
      </small>
    </a-modal>
  </a-drawer>
</template>

<script>
import { ref, onMounted, computed } from "vue";
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
  components: { CheckOutlined, PlusOutlined, LeftOutlined, RightOutlined, DeleteOutlined },
  setup(props, { emit }) {
    const { addEditRequestAdmin, loading, rules } = apiAdmin();
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

    // --- print chooser state ---
    const printModalVisible = ref(false);
    const selectedPrintSize = ref("A4");
    const autoOpenPrint = ref(true);

    const paymentRecordsColumns = ref([
      { title: t("payments.payment_mode"), dataIndex: "payment_mode" },
      { title: t("payments.amount"), dataIndex: "amount" },
      { title: t("common.action"), dataIndex: "action" },
    ]);

    onMounted(() => {
      axiosAdmin.get("payment-modes").then((response) => {
        paymentModes.value = response.data;
      });
    });

    const resetForm = () => {
      formData.value = { payment_mode_id: undefined, amount: 0, notes: "" };
    };

    const drawerClosed = () => {
      resetForm();
      allPaymentRecords.value = [];
      showAddForm.value = false;
      printModalVisible.value = false;
      emit("closed");
    };

    const onSubmit = () => {
      addEditRequestAdmin({
        url: "pos/payment",
        data: formData.value,
        success: () => {
          allPaymentRecords.value = [
            ...allPaymentRecords.value,
            { ...formData.value, id: Math.random().toString(36).slice(2) },
          ];
          resetForm();
          showAddForm.value = false;
        },
      });
    };

    const openPrintChooser = () => {
      printModalVisible.value = true;
    };

    const completeOrderAndEmitPrint = () => {
      const payload = {
        all_payments: allPaymentRecords.value,
        product_items: props.selectedProducts,
        details: props.data,
        print_pref: { size: selectedPrintSize.value },
      };

      addEditRequestAdmin({
        url: "pos/save",
        data: payload,
        successMessage: props.successMessage,
        success: (res) => {
          resetForm();
          allPaymentRecords.value = [];
          showAddForm.value = false;
          printModalVisible.value = false;

          emit("success", res.order);

          emit("print", {
            order: res.order,
            size: selectedPrintSize.value, // 'A4' | 'A5' | '80mm' | '58mm'
            autoOpen: autoOpenPrint.value,
          });
        },
      });
    };

    const goBack = () => {
      resetForm();
      showAddForm.value = false;
    };

    const getPaymentModeName = (paymentId) => {
      const selectedMode = find(paymentModes.value, ["xid", paymentId]);
      return selectedMode ? selectedMode.name : "-";
    };

    const deletePayment = (paymentId) => {
      allPaymentRecords.value = filter(allPaymentRecords.value, (p) => p.id != paymentId);
    };

    const totalEnteredAmount = computed(() => {
      const allPaymentSum = sumBy(allPaymentRecords.value, (p) => parseFloat(p.amount || 0));
      return allPaymentSum + parseFloat(formData.value.amount || 0);
    });

    return {
      loading, rules, paymentModes, allPaymentRecords, paymentRecordsColumns, showAddForm, formData,
      printModalVisible, selectedPrintSize, autoOpenPrint,
      drawerClosed, onSubmit, openPrintChooser, completeOrderAndEmitPrint, goBack,
      getPaymentModeName, deletePayment,
      appSetting, formatAmountCurrency, totalEnteredAmount,
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
