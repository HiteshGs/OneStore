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
      <a-row :gutter="16">
        <a-col
          :xs="24"
          :sm="24"
          :md="permsArray.includes('admin') ? 12 : 24"
          :lg="permsArray.includes('admin') ? 12 : 24"
        >
          <a-form-item
            :label="$t('expense.expense_category')"
            name="expense_category_id"
            :help="rules.expense_category_id ? rules.expense_category_id.message : null"
            :validateStatus="rules.expense_category_id ? 'error' : null"
            class="required"
          >
            <span style="display: flex">
              <a-select
                v-model:value="newFormData.expense_category_id"
                :placeholder="$t('common.select_default_text', [$t('expense.expense_category')])"
                :allowClear="true"
                optionFilterProp="label"
                show-search
              >
                <a-select-option
                  v-for="expenseCategory in expenseCategories"
                  :key="expenseCategory.xid"
                  :value="expenseCategory.xid"
                  :label="expenseCategory.name"
                >
                  {{ expenseCategory.name }}
                </a-select-option>
              </a-select>
              <ExpenseCategoryAddButton @onAddSuccess="expenseCategoryAdded" />
            </span>
          </a-form-item>
        </a-col>

        <a-col
          v-if="permsArray.includes('admin')"
          :xs="24"
          :sm="24"
          :md="permsArray.includes('admin') ? 12 : 24"
          :lg="permsArray.includes('admin') ? 12 : 24"
        >
          <a-form-item
            :label="$t('expense.created_by_user')"
            name="user_id"
            :help="rules.user_id ? rules.user_id.message : null"
            :validateStatus="rules.user_id ? 'error' : null"
          >
            <span style="display: flex">
              <a-select
                v-model:value="newFormData.user_id"
                :placeholder="$t('common.select_default_text', [$t('expense.user')])"
                :allowClear="true"
                option-label-prop="label"
                optionFilterProp="label"
                show-search
              >
                <a-select-option
                  v-for="staffMember in staffMembers"
                  :key="staffMember.xid"
                  :value="staffMember.xid"
                  :label="staffMember.name"
                >
                  <UserInfo :user="staffMember" size="small" />
                </a-select-option>
              </a-select>
              <StaffAddButton @onAddSuccess="staffMemberAdded" />
            </span>
          </a-form-item>
        </a-col>
      </a-row>

      <a-row :gutter="16">
        <a-col :xs="24" :sm="24" :md="12" :lg="12">
          <a-form-item
            :label="$t('expense.date')"
            name="date"
            :help="rules.date ? rules.date.message : null"
            :validateStatus="rules.date ? 'error' : null"
            class="required"
          >
            <DateTimePicker
              :dateTime="newFormData.date"
              @dateTimeChanged="(changedDateTime) => (newFormData.date = changedDateTime)"
            />
          </a-form-item>
        </a-col>

        <a-col :xs="24" :sm="24" :md="12" :lg="12">
          <a-form-item
            :label="$t('expense.amount')"
            name="amount"
            :help="rules.amount ? rules.amount.message : null"
            :validateStatus="rules.amount ? 'error' : null"
            class="required"
          >
            <a-input-number
              v-model:value="newFormData.amount"
              :placeholder="$t('common.placeholder_default_text', [$t('expense.amount')])"
              min="0"
              style="width: 100%"
            >
              <template #addonBefore>
                {{ appSetting.currency.symbol }}
              </template>
            </a-input-number>
          </a-form-item>
        </a-col>
      </a-row>

      <!-- Payment Mode (API-driven) -->
      <a-row :gutter="16">
        <a-col :xs="24" :sm="24" :md="12" :lg="12">
          <a-form-item
            :label="$t('expense.payment_mode')"
            name="payment_mode_id"
            :help="rules.payment_mode_id ? rules.payment_mode_id.message : null"
            :validateStatus="rules.payment_mode_id ? 'error' : null"
          >
            <a-select
              v-model:value="newFormData.payment_mode_id"
              :placeholder="$t('common.select_default_text', [$t('expense.payment_mode')])"
              :allowClear="true"
              optionFilterProp="label"
              show-search
              :loading="paymentModesLoading"
            >
              <a-select-option
                v-for="m in paymentModes"
                :key="m.xid"
                :value="m.xid"
                :label="m.name"
              >
                {{ m.name }}
                <span v-if="m.mode_type" style="opacity:.6"> ({{ m.mode_type }})</span>
              </a-select-option>
            </a-select>
          </a-form-item>
        </a-col>
      </a-row>
      <!-- /Payment Mode -->

      <a-row :gutter="16">
        <a-col :xs="24" :sm="24" :md="24" :lg="24">
          <a-form-item
            :label="$t('expense.bill')"
            name="bill"
            :help="rules.bill ? rules.bill.message : null"
            :validateStatus="rules.bill ? 'error' : null"
          >
            <UploadFile
              :acceptFormat="'image/*,.pdf'"
              :formData="newFormData"
              folder="expenses"
              uploadField="bill"
              @onFileUploaded="
                (file) => {
                  newFormData.bill = file.file;
                  newFormData.bill_url = file.file_url;
                }
              "
            />
          </a-form-item>
        </a-col>
      </a-row>

      <a-row :gutter="16">
        <a-col :xs="24" :sm="24" :md="24" :lg="24">
          <a-form-item
            :label="$t('expense.notes')"
            name="notes"
            :help="rules.notes ? rules.notes.message : null"
            :validateStatus="rules.notes ? 'error' : null"
          >
            <a-textarea
              v-model:value="newFormData.notes"
              :placeholder="$t('common.placeholder_default_text', [$t('expense.notes')])"
              :rows="4"
            />
          </a-form-item>
        </a-col>
      </a-row>
    </a-form>

    <template #footer>
      <a-button
        type="primary"
        @click="onSubmit"
        style="margin-right: 8px"
        :loading="loading"
      >
        <template #icon><SaveOutlined /></template>
        {{ addEditType == "add" ? $t("common.create") : $t("common.update") }}
      </a-button>
      <a-button @click="onClose">
        {{ $t("common.cancel") }}
      </a-button>
    </template>
  </a-drawer>
</template>

<script>
import { defineComponent, ref, computed, onMounted, watch } from "vue";
import { PlusOutlined, LoadingOutlined, SaveOutlined } from "@ant-design/icons-vue";
import apiAdmin from "../../../../common/composable/apiAdmin.js";
import UserInfo from "../../../../common/components/user/UserInfo.vue";
import common from "../../../../common/composable/common.js";
import ExpenseCategoryAddButton from "../expense-categories/AddButton.vue";
import StaffAddButton from "../../users/StaffAddButton.vue";
import UploadFile from "../../../../common/core/ui/file/UploadFile.vue";
import DateTimePicker from "../../../../common/components/common/calendar/DateTimePicker.vue";

// NOTE: axiosAdmin is assumed to be available globally in your app bootstrap.
// If not, import your axios instance explicitly:
// import axiosAdmin from "../../../../common/composable/axiosAdmin.js";

export default defineComponent({
  props: [
    "formData",
    "data",
    "visible",
    "url",
    "addEditType",
    "pageTitle",
    "successMessage",
  ],
  components: {
    PlusOutlined,
    LoadingOutlined,
    UserInfo,
    SaveOutlined,
    ExpenseCategoryAddButton,
    StaffAddButton,
    UploadFile,
    DateTimePicker,
  },
  setup(props, { emit }) {
    const { addEditRequestAdmin, loading, rules } = apiAdmin();
    const expenseCategories = ref([]);
    const staffMembers = ref([]);
    const { appSetting, disabledDate, permsArray, dayjs } = common();

    const expenseCategoryUrl = "expense-categories?limit=10000";
    const staffMemberUrl = "users?limit=10000";

    // Payment Modes (API-driven)
    const paymentModes = ref([]);
    const paymentModesLoading = ref(false);

    // Try a few common endpoints; keep only the one that works.
    const paymentModeCandidates = [
      "payment-modes?limit=10000",
      "payment-modes",
      "payment_modes",
      "payment-modes/list",
    ];

    const newFormData = ref({});

    const extractRows = (data) => {
      // Accept: {data:{data:[...]}}, {data:[...]}, or [...]
      const body = data?.data ?? data ?? [];
      if (Array.isArray(body)) return body;
      if (Array.isArray(body?.data)) return body.data;
      return [];
    };

    const loadPaymentModes = async () => {
      paymentModesLoading.value = true;
      for (const path of paymentModeCandidates) {
        try {
          const { data } = await axiosAdmin.get(path);
          const rows = extractRows(data).map((r) => ({
            xid: r.xid ?? r.id ?? r.uuid,
            name: r.name ?? r.title ?? "",
            mode_type: r.mode_type ?? r.type ?? null,
          }));
          if (rows.length) {
            paymentModes.value = rows;
            paymentModesLoading.value = false;
            return;
          }
        } catch (_) {
          // try next candidate
        }
      }
      paymentModes.value = [];
      paymentModesLoading.value = false;
    };

    const selectedPayment = computed(() =>
      paymentModes.value.find((m) => m?.xid === newFormData.value?.payment_mode_id)
    );

    onMounted(async () => {
      const expenseCategoriesPromise = axiosAdmin.get(expenseCategoryUrl);
      const staffMembersPromise = axiosAdmin.get(staffMemberUrl);
      const paymentModesPromise = loadPaymentModes();

      const [expenseCategoriesResponse, staffMembersResponse] = await Promise.all([
        expenseCategoriesPromise,
        staffMembersPromise,
        paymentModesPromise,
      ]);

      expenseCategories.value = expenseCategoriesResponse.data;
      staffMembers.value = staffMembersResponse.data;
    });

    const onSubmit = () => {
      // If your backend expects only payment_mode_id, payload is already fine.
      // Including readable fields for compatibility (won't hurt if ignored).
      const payload = {
        ...newFormData.value,
        payment_mode: selectedPayment.value?.name ?? null,
        payment_mode_type: selectedPayment.value?.mode_type ?? null,
      };

      addEditRequestAdmin({
        url: props.url,
        data: payload,
        successMessage: props.successMessage,
        success: (res) => {
          emit("addEditSuccess", res.xid);
        },
      });
    };

    const onClose = () => {
      rules.value = {};
      emit("closed");
    };

    const expenseCategoryAdded = () => {
      axiosAdmin.get(expenseCategoryUrl).then((response) => {
        expenseCategories.value = response.data;
      });
    };

    const staffMemberAdded = () => {
      axiosAdmin.get(staffMemberUrl).then((response) => {
        staffMembers.value = response.data;
      });
    };

    watch(
      () => props.visible,
      (newVal) => {
        if (newVal) {
          if (props.addEditType === "add") {
            newFormData.value = {
              ...props.formData,
              date: dayjs().utc().format("YYYY-MM-DDTHH:mm:ssZ"),
              payment_mode_id: null, // selected payment mode xid
            };
          } else {
            newFormData.value = {
              ...props.formData,
              payment_mode_id: props.formData?.payment_mode_id ?? null,
            };
          }
        }
      }
    );

    return {
      // state
      newFormData,
      loading,
      rules,

      // lookups
      expenseCategories,
      staffMembers,
      paymentModes,
      paymentModesLoading,

      // ui
      appSetting,
      disabledDate,
      permsArray,
      drawerWidth: window.innerWidth <= 991 ? "90%" : "45%",

      // actions
      onSubmit,
      onClose,
      expenseCategoryAdded,
      staffMemberAdded,

      // helpers
      selectedPayment,
    };
  },
});
</script>

<style>
.ant-calendar-picker {
  width: 100%;
}
</style>
