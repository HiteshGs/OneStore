import { reactive, ref } from 'vue';
import { useI18n } from "vue-i18n";

const fields = () => {
  const { t } = useI18n();
  const addEditUrl = "expenses";

  // ✅ include payment_mode_id and relation paymentMode{...}
  const url = ref(
    "expenses" +
    "?fields=" + [
      "id",
      "xid",
      "bill",
      "bill_url",
      "amount",
      "notes",
      "date",
      // category
      "expense_category_id",
      "x_expense_category_id",
      "expenseCategory{id,xid,name}",
      // user
      "user_id",
      "x_user_id",
      "user{id,xid,name}",
      // ✅ payment mode
      "payment_mode_id",
      "x_payment_mode_id",
      "paymentMode{id,xid,name,mode_type}"
    ].join(",")
  );

  // ✅ hash decode this FK too
  const hashableColumns = ['user_id', 'expense_category_id', 'payment_mode_id'];

  const preFetchData = reactive({
    expenseCategories: [],
    staffMembers: [],
    // ✅ for filter dropdown
    paymentModes: [],
  });

  const initData = {
    expense_category_id: undefined,
    amount: "",
    bill: undefined,
    bill_url: undefined,
    date: undefined,
    user_id: undefined,
    notes: "",
    // ✅ form also binds to this in AddEdit.vue
    payment_mode_id: undefined,
  };

  const columns = [
    { title: t("expense.expense_category"), dataIndex: "expense_category_id", sorter: true },
    { title: t("expense.amount"),           dataIndex: "amount",             sorter: true },
    { title: t("expense.date"),             dataIndex: "date",               sorter: true },
    { title: t("expense.created_by_user"),  dataIndex: "user_id",            sorter: true },
    // ✅ show name in list view
    { title: t("expense.payment_mode"),     dataIndex: "payment_mode_id",    sorter: false },
    { title: t("common.action"),            dataIndex: "action" },
  ];

  // ✅ add payment_mode_id as a filter key
  const filters = reactive({
    expense_category_id: undefined,
    user_id: undefined,
    payment_mode_id: undefined,
  });

  const getPreFetchData = () => {
    const expenseCategoriesPromise = axiosAdmin.get("expense-categories?limit=10000");
    const staffMembersPromise      = axiosAdmin.get("users?limit=10000");
    const paymentModesPromise      = axiosAdmin.get("payment-modes?limit=10000"); // ✅

    Promise.all([expenseCategoriesPromise, staffMembersPromise, paymentModesPromise]).then(
      ([expenseCategoriesResponse, staffMembersResponse, paymentModesResponse]) => {
        preFetchData.expenseCategories = expenseCategoriesResponse.data;
        preFetchData.staffMembers      = staffMembersResponse.data;
        preFetchData.paymentModes      = paymentModesResponse.data; // array of {xid,name,...}
      }
    );
  };

  return {
    url,
    addEditUrl,
    initData,
    columns,
    filters,
    hashableColumns,
    preFetchData,
    getPreFetchData,
  };
};

export default fields;