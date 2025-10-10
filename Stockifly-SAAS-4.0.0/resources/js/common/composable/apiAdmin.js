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

    const addEditRequestAdmin = (configObject) => {
        loading.value = true;
        const { url, data, success } = configObject;
        var formData = {};

        // Replace undefined values to null
        forEach(data, function (value, key) {
            if (value == undefined) {
                formData[key] = null;
            } else {
                formData[key] = value;
            }
        });

        axiosAdmin
            .post(url, formData)
            .then(response => {
                if (configObject.successMessage) {
                    notification.success({
                        placement: appSetting.value.rtl ? "bottomLeft" : "bottomRight",
                        message: t("common.success"),
                        description: configObject.successMessage
                    });
                }
                success(response.data);
                loading.value = false;
                rules.value = {};
            })
            .catch(errorResponse => {
                var err = errorResponse.data;
                const errorCode = errorResponse.status;
                var errorRules = {};

                if (errorCode == 422) {
                    if (err.error && typeof err.error.details != "undefined") {
                        var keys = Object.keys(err.error.details);
                        for (var i = 0; i < keys.length; i++) {
                            var key = keys[i].replace(".", "\\.");
                            errorRules[key] = {
                                required: true,
                                message: err.error.details[keys[i]][0],
                            };
                        }
                    }
                    rules.value = errorRules;
                    message.error(t("common.fix_errors"));
                }

                if (err && err.message) {
                    message.error(err.message);
                    err = { error: { ...err } }
                }

                if (configObject.error) {
                    configObject.error(err);
                }
                loading.value = false;
            });
    }

    const addEditFileRequestAdmin = (configObject) => {
        loading.value = true;
        const { url, data, success } = configObject;
        const formData = new FormData();

        // Robust append: support both files and plain values
        forEach(data, function (value, key) {
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
            .post(url, formData, {
                headers: { "Content-Type": "multipart/form-data" },
            })
            .then(response => {
                if (configObject.successMessage) {
                    notification.success({
                        placement: appSetting.value.rtl ? "bottomLeft" : "bottomRight",
                        message: t("common.success"),
                        description: configObject.successMessage
                    });
                }
                success(response.data);
                loading.value = false;
                rules.value = {};
            })
            .catch(errorResponse => {
                var err = errorResponse.data;
                const errorCode = errorResponse.status;
                var errorRules = {};

                if (errorCode == 422) {
                    if (err.error && typeof err.error.details != "undefined") {
                        var keys = Object.keys(err.error.details);
                        for (var i = 0; i < keys.length; i++) {
                            var key = keys[i].replace(".", "\\.");
                            errorRules[key] = {
                                required: true,
                                message: err.error.details[keys[i]][0],
                            };
                        }
                    }
                    rules.value = errorRules;
                    message.error(t("common.fix_errors"));
                }

                if (err && err.message) {
                    message.error(err.message);
                    err = { error: { ...err } }
                }

                if (configObject.error) {
                    configObject.error(err);
                }
                loading.value = false;
            });
    }

    return { loading, rules, addEditRequestAdmin, addEditFileRequestAdmin };
}

export default api;
