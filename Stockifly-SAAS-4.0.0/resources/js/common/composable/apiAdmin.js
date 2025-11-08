import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { forEach } from "lodash-es";
import { message, notification } from "ant-design-vue";
import common from "../composable/common";

// assumes axiosAdmin is available in your axios setup/import scope

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

  const formatDetails = (details) => {
    if (!details || typeof details !== "object") return [];
    const lines = [];
    Object.keys(details).forEach((field) => {
      const arr = Array.isArray(details[field]) ? details[field] : [details[field]];
      arr.forEach((msg) => lines.push(`${field}: ${String(msg)}`));
    });
    return lines;
  };

  const extractError = (err) => {
    const resp = err?.response;
    const status = resp?.status ?? null;
    const data = resp?.data ?? null;

    const apiError = data?.error;
    const payloadMessage =
      apiError?.message ||
      data?.message ||
      (typeof data === "string" ? data : null);

    const isTimeout = err?.code === "ECONNABORTED";
    const isNetwork = err?.message?.toLowerCase?.().includes("network");

    let topMsg;
    if (payloadMessage) {
      topMsg = String(payloadMessage);
    } else if (isTimeout) {
      topMsg = t("common.timeout") || "Request timed out";
    } else if (!resp && isNetwork) {
      topMsg = t("common.network_error") || "Network error";
    } else if (!resp) {
      topMsg = t("common.unknown_error") || "Unknown error";
    } else if (status >= 500) {
      topMsg = t("common.server_error") || "Server error";
    } else {
      topMsg = t("common.unknown_error") || "Unknown error";
    }

    const details = apiError?.details || data?.errors || null;
    const detailLines = formatDetails(details);

    return { status, data, topMsg, details, detailLines };
  };

  const showError = (topMsg, detailLines) => {
    const full = [topMsg, ...detailLines].join("\n");
    message.error(full);
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
        const { status, topMsg, details, detailLines } = extractError(err);

        if (status === 422 && details && typeof details === "object") {
          const errorRules = {};
          Object.keys(details).forEach((k) => {
            const key = k.replace(/\./g, "\\.");
            const msg = Array.isArray(details[k]) ? details[k][0] : String(details[k]);
            errorRules[key] = { required: true, message: msg };
          });
          rules.value = errorRules;
        }

        showError(topMsg, detailLines);

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
        const { status, topMsg, details, detailLines } = extractError(err);

        if (status === 422 && details && typeof details === "object") {
          const errorRules = {};
          Object.keys(details).forEach((k) => {
            const key = k.replace(/\./g, "\\.");
            const msg = Array.isArray(details[k]) ? details[k][0] : String(details[k]);
            errorRules[key] = { required: true, message: msg };
          });
          rules.value = errorRules;
        }

        showError(topMsg, detailLines);

        // eslint-disable-next-line no-console
        console.error("API FILE ERROR:", { status, err });

        if (configObject.error) configObject.error(err?.response?.data || err);
        loading.value = false;
      });
  };

  return { loading, rules, addEditRequestAdmin, addEditFileRequestAdmin };
};

export default api;
