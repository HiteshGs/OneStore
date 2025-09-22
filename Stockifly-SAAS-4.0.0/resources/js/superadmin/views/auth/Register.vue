<template>
    <div class="login-main-container">
        <a-row class="main-container-div">
            <a-col :xs="24" :sm="24" :md="24" :lg="8" class="bg-white">
                <a-row class="login-left-div">
                    <a-col
                        :xs="{ span: 20, offset: 2 }"
                        :sm="{ span: 20, offset: 2 }"
                        :md="{ span: 16, offset: 4 }"
                        :lg="{ span: 16, offset: 4 }"
                    >
                        <a-card
                            :title="null"
                            class="login-div"
                            :bordered="innerWidth <= 768 ? true : false"
                        >
                            <a-result
                                v-if="onRequestSend.success"
                                :title="$t('front_website.register_thank_you')"
                            >
                                <template #icon>
                                    <SmileTwoTone />
                                </template>
                                <template #extra>
                                    <a-button
                                        @click="
                                            () => $router.push({ name: 'admin.login' })
                                        "
                                        type="primary"
                                    >
                                        {{ $t("menu.login") }}
                                    </a-button>
                                </template>
                            </a-result>
                            <a-form v-else layout="vertical">
                                <div class="login-logo">
                                    <img
                                        class="login-img-logo"
                                        :src="globalSetting.light_logo_url"
                                    />
                                </div>
                                <br />
                                <div
                                    style="
                                        text-align: left;
                                        margin-top: 40px;
                                        margin-bottom: 32px;
                                    "
                                >
                                    <div
                                        style="
                                            font-weight: bold;
                                            font-size: 18px;
                                            margin-bottom: 2px;
                                        "
                                    >
                                        {{ $t("messages.register_business") }}
                                    </div>
                                    <div style="margin-bottom: 14px">
                                        {{ $t("messages.account_message") }}
                                    </div>
                                </div>
                                <a-alert
                                    v-if="onRequestSend.error != ''"
                                    :message="onRequestSend.error"
                                    type="error"
                                    show-icon
                                    class="mb-20 mt-10"
                                />
                                <!-- <a-alert
                                    v-if="onRequestSend.success"
                                    :message="$t('messages.login_success')"
                                    type="success"
                                    show-icon
                                    class="mb-20 mt-10"
                                /> -->
                                <a-form-item
                                    :label="$t('front_website.company_name')"
                                    name="company_name"
                                    :help="
                                        rules.company_name
                                            ? rules.company_name.message
                                            : null
                                    "
                                    :validateStatus="rules.company_name ? 'error' : null"
                                    class="required"
                                >
                                    <a-input
                                        v-model:value="credentials.company_name"
                                        :placeholder="
                                            $t('common.placeholder_default_text', [
                                                $t('front_website.company_name'),
                                            ])
                                        "
                                    />
                                </a-form-item>
                                <a-form-item
                                    :label="$t('front_website.phone')"
                                    name="company_phone"
                                    :help="
                                        rules.company_phone
                                            ? rules.company_phone.message
                                            : null
                                    "
                                    :validateStatus="rules.company_phone ? 'error' : null"
                                    class="required"
                                >
                                    <a-input
                                        v-model:value="credentials.company_phone"
                                        :placeholder="
                                            $t('common.placeholder_default_text', [
                                                $t('front_website.phone'),
                                            ])
                                        "
                                    />
                                </a-form-item>
                                <a-form-item
                                    :label="$t('front_website.email')"
                                    name="company_email"
                                    :help="
                                        rules.company_email
                                            ? rules.company_email.message
                                            : null
                                    "
                                    :validateStatus="rules.company_email ? 'error' : null"
                                    class="required"
                                >
                                    <a-input
                                        v-model:value="credentials.company_email"
                                        :placeholder="
                                            $t('common.placeholder_default_text', [
                                                $t('front_website.email'),
                                            ])
                                        "
                                    />
                                </a-form-item>
                                <a-form-item
                                    :label="$t('front_website.password')"
                                    name="password"
                                    :help="rules.password ? rules.password.message : null"
                                    :validateStatus="rules.password ? 'error' : null"
                                    class="required"
                                >
                                    <a-input-password
                                        v-model:value="credentials.password"
                                        :placeholder="
                                            $t('common.placeholder_default_text', [
                                                $t('front_website.password'),
                                            ])
                                        "
                                    />
                                </a-form-item>
                                <a-form-item
                                    :label="$t('front_website.confirm_password')"
                                    name="confirm_password"
                                    :help="
                                        rules.confirm_password
                                            ? rules.confirm_password.message
                                            : null
                                    "
                                    :validateStatus="
                                        rules.confirm_password ? 'error' : null
                                    "
                                    class="required"
                                >
                                    <a-input-password
                                        v-model:value="credentials.confirm_password"
                                        :placeholder="
                                            $t('common.placeholder_default_text', [
                                                $t('front_website.confirm_password'),
                                            ])
                                        "
                                    />
                                </a-form-item>

                                <a-form-item
                                    name="condition"
                                    :help="
                                        rules.condition ? rules.condition.message : null
                                    "
                                    :validateStatus="rules.condition ? 'error' : null"
                                >
                                    <a-checkbox-group
                                        v-model:value="credentials.condition"
                                        style="width: 100%"
                                    >
                                        <a-row :gutter="[16, 10]">
                                            <a-col :span="24">
                                                <a-checkbox value="on">
                                                    {{ $t("front_website.condition") }}
                                                </a-checkbox>
                                            </a-col>
                                        </a-row>
                                    </a-checkbox-group>
                                </a-form-item>

                                <a-form-item class="mt-30">
                                    <a-button
                                        :loading="loading"
                                        @click="onSubmit"
                                        class="login-btn"
                                        block
                                        type="primary"
                                    >
                                        {{ $t("menu.create_account") }}
                                    </a-button>
                                </a-form-item>

                                <a-form-item class="mt-10">
                                    <a-form-item
                                        style="text-align: center; font-weight: bold"
                                    >
                                        <a
                                            :loading="loading"
                                            @click="
                                                () =>
                                                    $router.push({ name: 'admin.login' })
                                            "
                                        >
                                            <ArrowLeftOutlined />
                                            {{ $t("menu.already_account_login_here") }}
                                        </a>
                                    </a-form-item>
                                </a-form-item>
                            </a-form>
                        </a-card>
                    </a-col>
                </a-row>
            </a-col>
            <a-col :xs="0" :sm="0" :md="24" :lg="16">
                <div class="right-login-div">
                    <img class="right-image" :src="loginBackground" />
                </div>
            </a-col>
        </a-row>
    </div>
</template>

<script>
import { defineComponent, reactive, ref } from "vue";
import { ArrowLeftOutlined } from "@ant-design/icons-vue";
import common from "../../../common/composable/common";
import apiAdmin from "../../../common/composable/apiAdmin";
import { SmileTwoTone } from "@ant-design/icons-vue";

export default defineComponent({
    components: {
        SmileTwoTone,
        ArrowLeftOutlined,
    },
    setup(props, { emit }) {
        const { addEditRequestAdmin, loading, rules } = apiAdmin();
        const { globalSetting } = common();
        const loginBackground = globalSetting.value.login_image_url;
        const credentials = reactive({
            company_name: "",
            company_email: "",
            company_phone: "",
            password: "",
            confirm_password: "",
            condition: [],
        });
        const onRequestSend = ref({
            error: "",
            success: "",
        });

        const onSubmit = () => {
            var formData = {
                ...credentials,
                condition: credentials.condition[0],
            };
            onRequestSend.value = {
                error: "",
                success: false,
            };

            addEditRequestAdmin({
                url: "new-register",
                data: formData,
                success: (response) => {
                    onRequestSend.value = {
                        error: "",
                        success: true,
                    };
                },
                error: (err) => {
                    onRequestSend.value = {
                        error: err.error.message ? err.error.message : "",
                        success: false,
                    };
                },
            });
        };

        return {
            loading,
            rules,
            credentials,
            onSubmit,
            onRequestSend,
            globalSetting,
            loginBackground,

            innerWidth: window.innerWidth,
        };
    },
});
</script>

<style lang="less">
.bg-white {
    background: #fff;
}

.login-main-container {
    background: #fff;
    height: 100vh;
}

.main-container-div {
    height: 100%;
}

.login-left-div {
    height: 100%;
    align-items: center;
}

.login-logo {
    text-align: center;
}

.login-img-logo {
    width: 150px;
}

.container-content {
    margin-top: 100px;
}

.login-div {
    border-radius: 10px;
}

.outer-div {
    margin: 0;
}

.right-login-div {
    background: #f8f8ff;
    height: 100%;
    display: flex;
    align-items: center;
}

.right-image {
    width: 100%;
    display: block;
    margin: 0 auto;
    height: calc(100vh);
}
</style>
