<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header :title="$t(`menu.warehouses`)" class="p-0" />
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size: 12px">
                <a-breadcrumb-item>
                    <router-link :to="{ name: 'admin.dashboard.index' }">
                        {{ $t(`menu.dashboard`) }}
                    </router-link>
                </a-breadcrumb-item>
                <a-breadcrumb-item>
                    {{ $t(`menu.settings`) }}
                </a-breadcrumb-item>
                <a-breadcrumb-item>
                    {{ $t(`menu.warehouses`) }}
                </a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <a-row>
        <a-col :xs="24" :sm="24" :md="24" :lg="4" :xl="4" class="bg-setting-sidebar">
            <SettingSidebar />
        </a-col>
        <a-col :xs="24" :sm="24" :md="24" :lg="20" :xl="20">
            <admin-page-filters>
                <!-- existing filters -->
            </admin-page-filters>

            <admin-page-table-content>
                <AddEdit
                    :addEditType="addEditType"
                    :visible="addEditVisible"
                    :url="addEditUrl"
                    @addEditSuccess="addEditSuccess"
                    @closed="onCloseAddEdit"
                    :formData="formData"
                    :data="viewData"
                    :pageTitle="pageTitle"
                    :successMessage="successMessage"
                />

                <a-row>
                    <a-col :span="24">
                        <div class="table-responsive">
                            <a-table
                                :row-selection="{
                                    selectedRowKeys: table.selectedRowKeys,
                                    onChange: onRowSelectChange,
                                    getCheckboxProps: (record) => ({
                                        disabled:
                                            (permsArray.includes('warehouses_delete') ||
                                                permsArray.includes('admin')) &&
                                            appSetting.x_warehouse_id != record.xid
                                                ? false
                                                : true,
                                        name: record.xid,
                                    }),
                                }"
                                :columns="columns"
                                :row-key="(record) => record.xid"
                                :data-source="table.data"
                                :pagination="table.pagination"
                                :loading="table.loading"
                                @change="handleTableChange"
                                bordered
                                size="middle"
                            >
                                <template #bodyCell="{ column, record }">
                                    <!-- Logo -->
                                    <template v-if="column.dataIndex === 'logo'">
                                        <a-image :width="48" :src="record.logo_url" />
                                    </template>

                                    <!-- Parent Warehouse -->
                                    <template v-if="column.dataIndex === 'parent_warehouse'">
                                        {{ record.parent?.name || '-' }}
                                    </template>

                                    <!-- Online Store -->
                                    <template v-if="column.dataIndex === 'online_store_enabled'">
                                        <OnlineStoreStatus
                                            :status="record.online_store_enabled"
                                            :x_warehouse_id="record.xid"
                                            @success="fetch"
                                        />
                                        <template v-if="record.online_store_enabled == 1">
                                            <br />
                                            <router-link
                                                :to="{
                                                    name: 'front.homepage',
                                                    params: {
                                                        warehouse: record.slug,
                                                    },
                                                }"
                                                target="_blank"
                                            >
                                                <a-button type="link" class="p-0 mt-5">
                                                    {{ $t("warehouse.view_online_store") }}
                                                </a-button>
                                            </router-link>
                                        </template>
                                    </template>

                                    <!-- Actions -->
                                    <template v-if="column.dataIndex === 'action'">
                                        <a-button
                                            v-if="permsArray.includes('warehouses_edit') || permsArray.includes('admin')"
                                            type="primary"
                                            @click="editItem(record)"
                                            style="margin-left: 4px"
                                        >
                                            <template #icon><EditOutlined /></template>
                                        </a-button>
                                        <a-button
                                            v-if="
                                                (permsArray.includes('warehouses_delete') ||
                                                    permsArray.includes('admin')) &&
                                                appSetting.x_warehouse_id != record.xid
                                            "
                                            type="primary"
                                            @click="showDeleteConfirm(record.xid)"
                                            style="margin-left: 4px"
                                        >
                                            <template #icon><DeleteOutlined /></template>
                                        </a-button>
                                    </template>
                                </template>
                            </a-table>
                        </div>
                    </a-col>
                </a-row>
            </admin-page-table-content>
        </a-col>
    </a-row>
</template>

<script>
import { onMounted } from "vue";
import { PlusOutlined, EditOutlined, DeleteOutlined } from "@ant-design/icons-vue";
import crud from "../../../../common/composable/crud";
import common from "../../../../common/composable/common";
import fields from "./fields";
import AddEdit from "./AddEdit.vue";
import SettingSidebar from "../SettingSidebar.vue";
import AdminPageHeader from "../../../../common/layouts/AdminPageHeader.vue";
import OnlineStoreStatus from "./OnlineStoreStatus.vue";

export default {
    components: {
        PlusOutlined,
        EditOutlined,
        DeleteOutlined,
        AddEdit,
        SettingSidebar,
        AdminPageHeader,
        OnlineStoreStatus,
    },
    setup() {
        const { permsArray, appSetting } = common();
        const { url, addEditUrl, initData, filterableColumns } = fields();
        const crudVariables = crud();

        // Define columns including Parent Warehouse
        const columns = [
            { title: "Name", dataIndex: "name", sorter: true },
            { title: "Parent Warehouse", dataIndex: "parent_warehouse", sorter: true },
            { title: "Logo", dataIndex: "logo" },
            { title: "Online Store", dataIndex: "online_store_enabled" },
            { title: "Action", dataIndex: "action" },
        ];

        onMounted(() => {
            crudVariables.tableUrl.value = { url };
            crudVariables.table.filterableColumns = filterableColumns;

            crudVariables.fetch({ page: 1 });

            crudVariables.crudUrl.value = addEditUrl;
            crudVariables.langKey.value = "warehouse";
            crudVariables.initData.value = { ...initData };
            crudVariables.formData.value = { ...initData };
        });

        return {
            appSetting,
            permsArray,
            columns,
            ...crudVariables,
            filterableColumns,
        };
    },
};
</script>
