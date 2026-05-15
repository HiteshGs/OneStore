<template>
  <a-drawer
    :title="$t('payments.order_payment')"
    :width="drawerWidth"
    :maskClosable="false"
    :open="visible"
    @close="drawerClosed"
  >
    <a-row>
      <!-- LEFT SUMMARY -->
      <a-col :xs="24" :md="8">
        <a-statistic
          :title="$t('stock.total_items')"
          :value="selectedProducts.length"
        />

        <a-statistic
          class="mt-20"
          :title="$t('stock.paying_amount')"
          :value="formatAmountCurrency(totalEnteredAmount)"
        />

        <a-statistic
          class="mt-20"
          :title="$t('stock.payable_amount')"
          :value="formatAmountCurrency(data.subtotal)"
        />

        <a-statistic
          class="mt-20"
          v-if="totalEnteredAmount <= data.subtotal"
          :title="$t('payments.due_amount')"
          :value="formatAmountCurrency(data.subtotal - totalEnteredAmount)"
        />
        <a-statistic
          class="mt-20"
          v-else
          :title="$t('stock.change_return')"
          :value="formatAmountCurrency(totalEnteredAmount - data.subtotal)"
        />
      </a-col>

      <!-- RIGHT CONTENT -->
      <a-col :xs="24" :md="16">
        <a-row :gutter="[24, 24]">
          <!-- ACTION BUTTONS -->
          <a-col :span="24" v-if="!showAddForm">
            <a-row :gutter="16" class="mt-20">
              <a-col :md="10">
                <a-button block type="primary" @click="showAddForm = true">
                  <PlusOutlined /> {{ $t('payments.add') }}
                </a-button>
              </a-col>

              <a-col :md="10">
                <a-button
                  block
                  :loading="loading"
                  @click="openEntryPersonDialog"
                >
                  {{ $t('stock.complete_order') }}
                  <RightOutlined />
                </a-button>
              </a-col>
            </a-row>
          </a-col>

          <!-- PAYMENT TABLE -->
          <a-col :span="24" v-if="!showAddForm">
            <a-table
              :dataSource="allPaymentRecords"
              :columns="paymentRecordsColumns"
              :pagination="false"
              rowKey="id"
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

          <!-- ADD PAYMENT -->
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

    <!-- ENTRY PERSON MODAL -->
    <a-modal
      :open="entryPersonModalVisible"
      title="Enter Entry Person Name"
      :maskClosable="false"
      :footer="null"
      @cancel="entryPersonModalVisible = false"
    >
      <a-input
        v-model:value="entryPersonName"
        placeholder="Entry person name (optional)"
      />

      <div style="margin-top: 16px; text-align: right">
        <a-button @click="entryPersonModalVisible = false">Cancel</a-button>
        <a-button type="primary" style="margin-left: 8px" @click="confirmEntryPerson">
          Continue
        </a-button>
      </div>
    </a-modal>

    <!-- PRINT MODAL -->
    <a-modal
      :open="printModalVisible"
      title="Select Print Layout"
      okText="Complete & Print"
      :confirmLoading="loading"
      :maskClosable="false"
      @ok="completeOrderAndEmitPrint"
      @cancel="printModalVisible = false"
    >
      <a-radio-group v-model:value="selectedPrintSize">
        <a-radio value="A4">A4</a-radio>
        <a-radio value="retail">Retail Invoice</a-radio>
        <a-radio value="A5">A5</a-radio>
        <a-radio value="80mm">Thermal 80mm</a-radio>
        <a-radio value="58mm">Thermal 58mm</a-radio>
      </a-radio-group>

      <div class="mt-20">
        <a-switch v-model:checked="autoOpenPrint" />
        <span class="ml-10">Print Preview</span>
      </div>
    </a-modal>

    <!-- DMART INVOICE MODAL -->
    <DmartInvoice
      :visible="dmartInvoiceVisible"
      :order="dmartOrderData"
      :description="dmartDescription"
      @closed="dmartInvoiceVisible = false"
      @success="onDmartInvoiceSuccess"
    />
  </a-drawer>
</template>

<script>
import { ref, computed, onMounted } from "vue";
import {
  CheckOutlined,
  PlusOutlined,
  RightOutlined,
  DeleteOutlined,
} from "@ant-design/icons-vue";
import { find, filter, sumBy } from "lodash-es";
import common from "../../../../common/composable/common";
import apiAdmin from "../../../../common/composable/apiAdmin";
import DmartInvoice from "./dmartinvoice.vue";

export default {
  components: {
    DmartInvoice,
  },
  props: ["visible", "data", "selectedProducts"],
  emits: ["closed", "success", "print"],
  setup(props, { emit }) {
    const ENTRY_PERSON_KEY = "pos_entry_person_name";
    const PRODUCT_HSN_MAP_KEY = "pos_product_hsn_map";

    const { addEditRequestAdmin, loading } = apiAdmin();
    const { appSetting, formatAmountCurrency } = common();

    const paymentModes = ref([]);
    const allPaymentRecords = ref([]);
    const showAddForm = ref(false);

    const formData = ref({
      payment_mode_id: undefined,
      amount: 0,
      notes: "",
    });

    const entryPersonModalVisible = ref(false);
    const entryPersonName = ref("");

    const printModalVisible = ref(false);
    const selectedPrintSize = ref("A4");
    const autoOpenPrint = ref(true);

    const dmartInvoiceVisible = ref(false);
    const dmartOrderData = ref(null);
    const dmartDescription = ref("");

    onMounted(() => {
      axiosAdmin.get("payment-modes").then(res => {
        paymentModes.value = res.data;
      });
    });

    const openEntryPersonDialog = () => {
      entryPersonName.value = localStorage.getItem(ENTRY_PERSON_KEY) || "";
      entryPersonModalVisible.value = true;
    };

    const confirmEntryPerson = () => {
      const value = entryPersonName.value.trim();
      value
        ? localStorage.setItem(ENTRY_PERSON_KEY, value)
        : localStorage.removeItem(ENTRY_PERSON_KEY);

      entryPersonModalVisible.value = false;
      printModalVisible.value = true;
    };

    const onSubmit = () => {
      allPaymentRecords.value.push({
        ...formData.value,
        id: crypto.randomUUID(),
      });
      formData.value = { payment_mode_id: undefined, amount: 0, notes: "" };
      showAddForm.value = false;
    };

   const completeOrderAndEmitPrint = () => {
  // ✅ VALIDATE DETAILS OBJECT
  if (!props.data || typeof props.data !== 'object') {
    console.error('❌ Invalid details object:', props.data);
    return;
  }

  // ✅ ENSURE ALL REQUIRED FIELDS HAVE VALUES (default to 0 if missing)
  const details = {
    subtotal: Number(props.data.subtotal) || 0,
    tax_amount: Number(props.data.tax_amount) || 0,
    discount: Number(props.data.discount) || 0,
    shipping: Number(props.data.shipping) || 0,
    tax_rate: Number(props.data.tax_rate) || 0,
    user_id: props.data.user_id || null,
    tax_id: props.data.tax_id || null,
  };

  // ✅ VALIDATE THAT SUBTOTAL IS NOT ZERO
  if (details.subtotal <= 0) {
    console.error('❌ Subtotal must be greater than 0');
    return;
  }

  // ✅ VALIDATE PRODUCT ITEMS
  if (!props.selectedProducts || props.selectedProducts.length === 0) {
    console.error('❌ No products selected');
    return;
  }

  // ✅ STORE HSN MAP LOCALLY
  const hsnMap = {};
  props.selectedProducts.forEach(p => {
    if (p.xid && p.hsn_code) {
      hsnMap[p.xid] = p.hsn_code;
    }
  });

  Object.keys(hsnMap).length
    ? localStorage.setItem(PRODUCT_HSN_MAP_KEY, JSON.stringify(hsnMap))
    : localStorage.removeItem(PRODUCT_HSN_MAP_KEY);

  // ✅ PREPARE PRODUCT ITEMS WITH ALL REQUIRED FIELDS
  const productItems = props.selectedProducts.map(p => ({
    xid: p.xid,
    name: p.name,
    quantity: Number(p.quantity) || 1,
    unit_price: Number(p.unit_price) || 0,
    single_unit_price: Number(p.single_unit_price) || 0,
    subtotal: Number(p.subtotal) || 0,
    tax_rate: Number(p.tax_rate) || 0,
    tax_type: p.tax_type || 'exclusive',
    total_tax: Number(p.total_tax) || 0,
    discount_rate: Number(p.discount_rate) || 0,
    total_discount: Number(p.total_discount) || 0,
    hsn_code: p.hsn_code || null,
    x_tax_id: p.x_tax_id || null,
    x_unit_id: p.x_unit_id || null,
    product_type: p.product_type || 'product',
  }));

  // ✅ API CALL WITH VALIDATED DATA
  console.log("📤 Sending validated data to pos/save:", {
    all_payments: allPaymentRecords.value,
    product_items_count: productItems.length,
    details: details,
    entry_person_name: localStorage.getItem(ENTRY_PERSON_KEY) || null,
  });

  addEditRequestAdmin({
    url: "pos/save",
    data: {
      all_payments: allPaymentRecords.value,
      product_items: productItems,
      details: details,
      entry_person_name: localStorage.getItem(ENTRY_PERSON_KEY) || null,
      print_pref: { size: selectedPrintSize.value },
    },
    success: res => {
      // Check if retail invoice is selected
      if (selectedPrintSize.value === "retail") {
        dmartOrderData.value = res.order;
        dmartDescription.value = "";
        dmartInvoiceVisible.value = true;
        printModalVisible.value = false;
        
        emit("success", { ...res.order, isRetailInvoice: true });
      } else {
        emit("success", res.order);
        emit("print", {
          order: res.order,
          size: selectedPrintSize.value,
          autoOpen: autoOpenPrint.value,
        });
      }
    },
    error: (err) => {
      console.error("❌ API Error:", err);
      // Optional: Show error message to user
    }
  });
};

    const onDmartInvoiceSuccess = () => {
      dmartInvoiceVisible.value = false;
    };

    const drawerClosed = () => {
      allPaymentRecords.value = [];
      entryPersonName.value = "";
      entryPersonModalVisible.value = false;
      printModalVisible.value = false;
      dmartInvoiceVisible.value = false;
      dmartOrderData.value = null;
      dmartDescription.value = "";
      showAddForm.value = false;
      emit("closed");
    };

    const deletePayment = id => {
      allPaymentRecords.value = filter(allPaymentRecords.value, p => p.id !== id);
    };

    const getPaymentModeName = id => {
      const m = find(paymentModes.value, ["xid", id]);
      return m ? m.name : "-";
    };

    const totalEnteredAmount = computed(() =>
      sumBy(allPaymentRecords.value, p => Number(p.amount || 0))
    );

    return {
      loading,
      paymentModes,
      allPaymentRecords,
      showAddForm,
      formData,
      entryPersonModalVisible,
      entryPersonName,
      printModalVisible,
      selectedPrintSize,
      autoOpenPrint,
      dmartInvoiceVisible,
      dmartOrderData,
      dmartDescription,
      openEntryPersonDialog,
      confirmEntryPerson,
      onSubmit,
      completeOrderAndEmitPrint,
      onDmartInvoiceSuccess,
      drawerClosed,
      deletePayment,
      getPaymentModeName,
      appSetting,
      formatAmountCurrency,
      totalEnteredAmount,
      drawerWidth: window.innerWidth <= 991 ? "90%" : "50%",
    };
  },
};
</script>

<style>
.mt-20 { margin-top: 20px; }
.ml-10 { margin-left: 10px; }
</style>
