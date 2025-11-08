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

  // ---- i18n helper so untranslated keys don't appear to users ----
  const tOrDefault = (key, fallback) => {
    try {
      const out = t ? t(key) : "";
      if (!out || out === key) return fallback;
      return out;
    } catch {
      return fallback;
    }
  };

  const successToast = (desc) => {
    if (!desc) return;
    notification.success({
      placement: appSetting.value.rtl ? "bottomLeft" : "bottomRight",
      message: tOrDefault("common.success", "Success"),
      description: desc,
    });
  };

  // details => ["name: The name field is required.", ...]
  const formatDetails = (details) => {
    if (!details || typeof details !== "object") return [];
    const lines = [];
    Object.keys(details).forEach((field) => {
      const arr = Array.isArray(details[field]) ? details[field] : [details[field]];
      arr.forEach((msg) => lines.push(`${field}: ${String(msg)}`));
    });
    return lines;
  };

  // Normalize error / response shape
  const extractError = (err) => {
    // Support both AxiosError and "plain response" (because of interceptors)
    const resp =
      err?.response || // normal axios error
      (err && typeof err.status !== "undefined" && typeof err.data !== "undefined"
        ? err
        : null);

    const status = resp?.status ?? null;
    const data = resp?.data ?? null;

    const apiError = data?.error;
    const payloadMessage =
      apiError?.message ||
      data?.message ||
      (typeof data === "string" ? data : null);

    const isTimeout = err?.code === "ECONNABORTED";
    const isNetwork = err?.message?.toLowerCase?.().includes("network");

    const timeoutMsg = tOrDefault("common.timeout", "Request timed out");
    const networkMsg = tOrDefault("common.network_error", "Network error");
    const unknownMsg = tOrDefault("common.unknown_error", "Unknown error");
    const serverMsg  = tOrDefault("common.server_error", "Server error");

    let topMsg;
    if (payloadMessage) {
      topMsg = String(payloadMessage);
    } else if (isTimeout) {
      topMsg = timeoutMsg;
    } else if (!resp && isNetwork) {
      topMsg = networkMsg;
    } else if (!resp) {
      topMsg = unknownMsg;
    } else if (status >= 500) {
      topMsg = serverMsg;
    } else {
      topMsg = unknownMsg;
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
            const key = k.replace(/\./g, "\\."); // support nested fields
            const msg = Array.isArray(details[k]) ? details[k][0] : String(details[k]);
            errorRules[key] = { required: true, message: msg };
          });
          rules.value = errorRules;
        }

        showError(topMsg, detailLines);

        // eslint-disable-next-line no-console
        console.error("API ERROR:", { status, err });

        if (configObject.error) configObject.error(err?.data || err?.response?.data || err);
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

        if (configObject.error) configObject.error(err?.data || err?.response?.data || err);
        loading.value = false;
      });
  };

  return { loading, rules, addEditRequestAdmin, addEditFileRequestAdmin };
};

export default api;
