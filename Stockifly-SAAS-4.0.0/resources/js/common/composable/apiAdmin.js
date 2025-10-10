import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { forEach } from "lodash-es";
import { message, notification } from "ant-design-vue";
import common from "../composable/common";

const api = () => {
  const loading = ref(false);
  const rules = ref({});
  const { t } = useI18n();
  const { appSetting } = common();

  const successToast = (desc) => {
    if (!desc) return;
    notification.success({
      placement: appSetting.value.rtl ? "bottomLeft" : "bottomRight",
      message: t("common.success"),
      description: desc,
    });
  };

  const extractError = (err) => {
    // Axios puts payload under err.response
    const resp = err?.response || {};
    const status = resp.status;
    const data = resp.data || {};
    // Common API shapes:
    // { message, error: { message, details: { field: [msg] } } }
    const topMsg =
      data?.error?.message ||
      data?.message ||
      (typeof data === "string" ? data : null) ||
      "Unknown error";
    const details = data?.error?.details || {};
    return { status, data, topMsg, details };
  };

  const addEditRequestAdmin = (configObject) => {
    loading.value = true;
    const { url, data, success } = configObject;
    const formData = {};

    forEach(data, (value, key) => {
      formData[key] = value === undefined ? null : value;
    });

    axiosAdmin
      .post(url, formData)
      .then((response) => {
        successToast(configObject.successMessage);
        success(response.data);
        loading.value = false;
        rules.value = {};
      })
      .catch((err) => {
        const { status, topMsg, details } = extractError(err);

        // Build rules for 422 validation payloads
        if (status === 422 && details && typeof details === "object") {
          const errorRules = {};
          Object.keys(details).forEach((k) => {
            const key = k.replace(".", "\\.");
            errorRules[key] = { required: true, message: details[k][0] };
          });
          rules.value = errorRules;
          message.error(t("common.fix_errors"));
        } else {
          message.error(topMsg);
        }

        // Help you debug in browser console
        // eslint-disable-next-line no-console
        console.error("API ERROR:", { status, err });

        if (configObject.error) configObject.error(err?.response?.data || err);
        loading.value = false;
      });
  };

  const addEditFileRequestAdmin = (configObject) => {
    loading.value = true;
    const { url, data, success } = configObject;
    const formData = new FormData();

    forEach(data, (value, key) => {
      if (value === undefined || value === null) {
        formData.append(key, "");
      } else if (value?.originFileObj) {
        const file = value.originFileObj;
        formData.append(key, file, value.name || file.name);
      } else {
        formData.append(key, value);
      }
    });

    axiosAdmin
      .post(url, formData, { headers: { "Content-Type": "multipart/form-data" } })
      .then((response) => {
        successToast(configObject.successMessage);
        success(response.data);
        loading.value = false;
        rules.value = {};
      })
      .catch((err) => {
        const { status, topMsg, details } = extractError(err);

        if (status === 422 && details && typeof details === "object") {
          const errorRules = {};
          Object.keys(details).forEach((k) => {
            const key = k.replace(".", "\\.");
            errorRules[key] = { required: true, message: details[k][0] };
          });
          rules.value = errorRules;
          message.error(t("common.fix_errors"));
        } else {
          message.error(topMsg);
        }

        // Debug:
        // eslint-disable-next-line no-console
        console.error("API FILE ERROR:", { status, err });

        if (configObject.error) configObject.error(err?.response?.data || err);
        loading.value = false;
      });
  };

  return { loading, rules, addEditRequestAdmin, addEditFileRequestAdmin };
};

export default api;
