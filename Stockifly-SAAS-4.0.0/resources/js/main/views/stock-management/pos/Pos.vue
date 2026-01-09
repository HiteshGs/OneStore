<template>
    <a-card
        class="page-content-sub-header breadcrumb-left-border"
        :bodyStyle="{ padding: '0px', margin: '0px 16px 0' }"
    >
        <a-row>
            <a-col :span="24">
                <a-page-header
                    :title="$t('menu.pos')"
                    @back="() => $router.go(-1)"
                    class="p-0"
                >
                    <template v-if="innerWidth <= 768" #extra>
                        <span style="display: flex">
                            <a-select
                                v-model:value="formData.user_id"
                                :placeholder="$t('user.walk_in_customer')"
                                style="width: 100%"
                                optionFilterProp="title"
                                show-search
                                @change="handleCustomerChange"
                            >
                                <a-select-option
                                    v-for="customer in customers"
                                    :key="customer.xid"
                                    :title="customer.name"
                                    :value="customer.xid"
                                >
                                    {{ customer.name }}
                                    <span v-if="customer.phone && customer.phone != ''">
                                        <br />
                                        {{ customer.phone }}
                                    </span>
                                </a-select-option>
                            </a-select>
                            <CustomerAddButton @onAddSuccess="customerAdded" />
                        </span>
                    </template>
                </a-page-header>
            </a-col>
        </a-row>
    </a-card>

    <a-form layout="vertical">
        <a-row
            v-if="innerWidth >= 768"
            :gutter="[8, 8]"
            class="mt-5"
            style="margin: 10px 16px 0"
        >
            <a-col :xs="24" :sm="24" :md="24" :lg="10" :xl="10">
                <div class="pos-left-wrapper">
                    <div class="pos-left-header">
                        <a-card class="left-pos-top" :style="{ marginBottom: '10px' }">
                            <div class="bill-filters">
                                <a-row :gutter="16">
                                    <a-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24">
                                        <span style="display: flex">
                                            <a-select
                                                v-model:value="formData.user_id"
                                                :placeholder="$t('user.walk_in_customer')"
                                                style="width: 100%"
                                                optionFilterProp="title"
                                                show-search
                                                @change="handleCustomerChange"
                                            >
                                                <a-select-option
                                                    v-for="customer in customers"
                                                    :key="customer.xid"
                                                    :title="customer.name"
                                                    :value="customer.xid"
                                                >
                                                    {{ customer.name }}
                                                    <span
                                                        v-if="
                                                            customer.phone &&
                                                            customer.phone != ''
                                                        "
                                                    >
                                                        <br />
                                                        {{ customer.phone }}
                                                    </span>
                                                </a-select-option>
                                            </a-select>
                                            <CustomerAddButton
                                                @onAddSuccess="customerAdded"
                                            />
                                        </span>
                                    </a-col>
                                </a-row>
                                <a-row class="mt-20 mb-30">
                                    <a-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24">
                                        <span style="display: flex">
                                            <a-select
                                                :value="null"
                                                :searchValue="orderSearchTerm"
                                                show-search
                                                :filter-option="false"
                                                :placeholder="
                                                    $t('product.search_scan_product')
                                                "
                                                style="width: 100%"
                                                :not-found-content="
                                                    productFetching ? undefined : null
                                                "
                                                @search="
                                                    (searchedValue) => {
                                                        orderSearchTerm = searchedValue;
                                                        fetchProducts(searchedValue);
                                                    }
                                                "
                                                option-label-prop="label"
                                                @focus="products = []"
                                                @select="searchValueSelected"
                                                @inputKeyDown="inputValueChanged"
                                            >
                                                <template #suffixIcon>
                                                    <SearchOutlined />
                                                </template>
                                                <template
                                                    v-if="productFetching"
                                                    #notFoundContent
                                                >
                                                    <a-spin size="small" />
                                                </template>
                                                <a-select-option
                                                    v-for="product in products"
                                                    :key="product.xid"
                                                    :value="product.xid"
                                                    :label="product.name"
                                                    :product="product"
                                                >
                                                    => {{ product.name }}
                                                </a-select-option>
                                            </a-select>
                                        </span>
                                    </a-col>
                                </a-row>
                            </div>
                        </a-card>
                    </div>
                    <div class="pos-left-content">
                        <a-card
                            class="left-pos-middle-table"
                            :style="{ marginBottom: '10px' }"
                        >
                            <div class="bill-body">
                                <div class="bill-table">
                                    <a-row class="mt-20 mb-30">
                                        <a-col :xs="24" :sm="24" :md="24" :lg="24">
                                            <a-table
                                                :row-key="(record) => record.xid"
                                                :dataSource="selectedProducts"
                                                :columns="orderItemColumns"
                                                :pagination="false"
                                                size="middle"
                                            >
                                                <template #bodyCell="{ column, record }">
                                                    <template
                                                        v-if="column.dataIndex === 'name'"
                                                    >
                                                        {{ record.name }} <br />
                                                        <small
                                                            v-if="
                                                                record.product_type !=
                                                                'service'
                                                            "
                                                        >
                                                            <a-typography-text code>
                                                                {{
                                                                    $t("product.avl_qty")
                                                                }}
                                                                {{
                                                                    `${record.stock_quantity}${record.unit_short_name}`
                                                                }}
                                                            </a-typography-text>
                                                        </small>
                                                    </template>
                                                    <template v-if="column.dataIndex === 'hsn_code'">
    <span>
        {{ record.hsn_code || '-' }}
    </span>
</template>

                                                    <template
                                                        v-if="
                                                            column.dataIndex ===
                                                            'unit_quantity'
                                                        "
                                                    >
                                                        <a-input-number
                                                            id="inputNumber"
                                                            v-model:value="
                                                                record.quantity
                                                            "
                                                            :min="0"
                                                            @change="
                                                                quantityChanged(record)
                                                            "
                                                        />
                                                    </template>
                                         <template v-if="column.dataIndex === 'subtotal'">
    <div class="subtotal-cell">
        <!-- Main: show subtotal as-is -->
        <div class="subtotal-main">
            {{ formatAmountCurrency(record.subtotal) }}
        </div>

        <!-- Only show badge if tax exists -->
        <div
            v-if="record.tax_rate !== null && record.tax_rate !== undefined && Number(record.tax_rate) > 0"
            class="subtotal-meta"
        >
      <a-tag color="gold" class="subtotal-tag">
    GST {{ record.tax_rate }}%
</a-tag>


        </div>
    </div>
</template>



                                                    <template
                                                        v-if="
                                                            column.dataIndex === 'action'
                                                        "
                                                    >
                                                        <a-button
                                                            type="primary"
                                                            @click="editItem(record)"
                                                            style="
                                                                margin-left: 4px;
                                                                margin-top: 4px;
                                                            "
                                                        >
                                                            <template #icon
                                                                ><EditOutlined
                                                            /></template>
                                                        </a-button>
                                                        <a-button
                                                            type="primary"
                                                            @click="
                                                                showDeleteConfirm(record)
                                                            "
                                                            style="
                                                                margin-left: 4px;
                                                                margin-top: 4px;
                                                            "
                                                        >
                                                            <template #icon
                                                                ><DeleteOutlined
                                                            /></template>
                                                        </a-button>
                                                    </template>
                                                </template>
                                            </a-table>
                                        </a-col>
                                    </a-row>
                                </div>
                            </div>
                        </a-card>
                    </div>
                    <div class="pos-left-footer">
                        <a-card>
                            <div class="bill-footer">
                                <a-row :gutter="[16, 16]">
                                    <a-col :xs="24" :sm="24" :md="8" :lg="8" :xl="8">
                                        <a-form-item :label="$t('stock.order_tax')">
                                            <a-select
                                                v-model:value="formData.tax_id"
                                                :placeholder="
                                                    $t('common.select_default_text', [
                                                        $t('stock.order_tax'),
                                                    ])
                                                "
                                                :allowClear="true"
                                                style="width: 100%"
                                                @change="taxChanged"
                                            >
                                                <a-select-option
                                                    v-for="tax in taxes"
                                                    :key="tax.xid"
                                                    :value="tax.xid"
                                                    :tax="tax"
                                                >
                                                    {{ tax.name }} ({{ tax.rate }}%)
                                                </a-select-option>
                                            </a-select>
                                        </a-form-item>
                                    </a-col>
                                    <a-col :xs="24" :sm="24" :md="8" :lg="8" :xl="8">
                                        <a-form-item :label="$t('stock.discount')">
                                            <a-input-group compact>
                                                <a-select
                                                    v-model:value="formData.discount_type"
                                                    @change="recalculateFinalTotal"
                                                    style="width: 30%"
                                                >
                                                    <a-select-option value="percentage">
                                                        %
                                                    </a-select-option>
                                                    <a-select-option value="fixed">
                                                        {{ appSetting.currency.symbol }}
                                                    </a-select-option>
                                                </a-select>
                                                <a-input-number
                                                    v-model:value="
                                                        formData.discount_value
                                                    "
                                                    :placeholder="
                                                        $t(
                                                            'common.placeholder_default_text',
                                                            [$t('stock.discount')]
                                                        )
                                                    "
                                                    @change="recalculateFinalTotal"
                                                    min="0"
                                                    style="width: 70%"
                                                />
                                            </a-input-group>
                                        </a-form-item>
                                    </a-col>
                                    <a-col :xs="24" :sm="24" :md="8" :lg="8" :xl="8">
                                        <a-form-item :label="$t('stock.shipping')">
                                            <a-input-number
                                                v-model:value="formData.shipping"
                                                :placeholder="
                                                    $t(
                                                        'common.placeholder_default_text',
                                                        [$t('stock.shipping')]
                                                    )
                                                "
                                                @change="recalculateFinalTotal"
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
                            </div>
                        </a-card>
                        <div
                            :style="{
                                right: 0,
                                bottom: 20,
                                width: '100%',
                                padding: '10px 16px',
                                background: '#fff',
                                textAlign: 'right',
                                zIndex: 1,
                            }"
                        >
                            <a-row :gutter="16">
                                <a-col :xs="24" :sm="24" :md="10" :lg="10" :xl="10">
                                    <a-row
                                        :gutter="16"
                                        :style="{ background: '#dbdbdb', padding: '5px' }"
                                    >
                                        <a-col
                                            :xs="24"
                                            :sm="24"
                                            :md="12"
                                            :lg="12"
                                            :xl="12"
                                        >
                                            <span class="pos-grand-total">
                                                {{ $t("stock.grand_total") }} :
                                            </span>
                                        </a-col>
                                        <a-col
                                            :xs="24"
                                            :sm="24"
                                            :md="12"
                                            :lg="12"
                                            :xl="12"
                                        >
                                            <span class="pos-grand-total">
                                                {{
                                                    formatAmountCurrency(
                                                        formData.subtotal
                                                    )
                                                }}
                                            </span>
                                        </a-col>
                                    </a-row>
                                </a-col>
                                <a-col
                                    :xs="24"
                                    :sm="24"
                                    :md="6"
                                    :lg="6"
                                    :xl="6"
                                    class="mt-10"
                                >
                                    <small>
                                        {{ $t("product.tax") }} :
                                        {{ formatAmountCurrency(formData.tax_amount) }} |
                                        {{ $t("product.discount") }} :
                                        {{ formatAmountCurrency(formData.discount) }}
                                    </small>
                                </a-col>
                                <a-col :xs="24" :sm="24" :md="8" :lg="8" :xl="8">
                                    <a-space>
                                        <a-button
                                            type="primary"
                                            @click="payNow"
                                            :disabled="
                                                formData.subtotal <= 0 ||
                                                formData.user_id == undefined ||
                                                formData.user_id == '' ||
                                                !formData.user_id
                                            "
                                        >
                                            {{ $t("stock.pay_now") }}
                                        </a-button>
                                        <a-button @click="resetPos">
                                            {{ $t("stock.reset") }}
                                        </a-button>
                                    </a-space>
                                </a-col>
                            </a-row>
                        </div>
                    </div>
                </div>
            </a-col>
            <a-col class="right-pos-sidebar" :xs="24" :sm="24" :md="24" :lg="14" :xl="14">
                <perfect-scrollbar
                    :options="{
                        wheelSpeed: 1,
                        swipeEasing: true,
                        suppressScrollX: true,
                    }"
                >
                    <PosLayout1
                        v-if="postLayout == 1"
                        :brands="brands"
                        :categories="categories"
                        :formData="formData"
                        @changed="reFetchProducts"
                    />

                    <PosLayout2
                        v-else
                        :brands="brands"
                        :categories="categories"
                        :formData="formData"
                        @changed="reFetchProducts"
                    />

                    <a-row v-if="productLists.length > 0" :gutter="30">
                        <a-col
                            v-for="item in productLists"
                            :key="item.xid"
                            :xxl="6"
                            :lg="6"
                            :md="12"
                            :xs="24"
                            @click="selectSaleProduct(item)"
                        >
                            <ProductCardNew :product="item" />
                        </a-col>
                    </a-row>
                    <a-row v-else>
                        <a-col :span="24">
                            <a-result
                                :title="$t('stock.no_product_found')"
                                :style="{ marginTop: '20%' }"
                            />
                        </a-col>
                    </a-row>
                </perfect-scrollbar>
            </a-col>
        </a-row>

        <a-row v-else :gutter="[8, 8]" class="mt-5" style="margin: 10px 16px 0">
            <a-col :span="24">
                <span style="display: flex">
                    <a-select
                        :value="null"
                        :searchValue="orderSearchTerm"
                        show-search
                        :filter-option="false"
                        :placeholder="$t('product.search_scan_product')"
                        style="width: 90%"
                        :not-found-content="productFetching ? undefined : null"
                        @search="
                            (searchedValue) => {
                                orderSearchTerm = searchedValue;
                                fetchProducts(searchedValue);
                            }
                        "
                        option-label-prop="label"
                        @focus="products = []"
                        @select="searchValueSelected"
                        @inputKeyDown="inputValueChanged"
                    >
                        <template #suffixIcon>
                            <SearchOutlined />
                        </template>
                        <template v-if="productFetching" #notFoundContent>
                            <a-spin size="small" />
                        </template>
                        <a-select-option
                            v-for="product in products"
                            :key="product.xid"
                            :value="product.xid"
                            :label="product.name"
                            :product="product"
                        >
                            => {{ product.name }}
                        </a-select-option>
                    </a-select>
                    <a-button
                        v-if="showMobileCart"
                        class="ml-5"
                        style="width: 100%"
                        @click="() => (showMobileCart = false)"
                        type="primary"
                    >
                        <template #icon>
                            <ShoppingCartOutlined />
                        </template>
                    </a-button>
                    <a-button
                        v-else
                        class="ml-5"
                        style="width: 100%"
                        @click="() => (showMobileCart = true)"
                        type="primary"
                    >
                        <template #icon>
                            <UnorderedListOutlined />
                        </template>
                    </a-button>
                </span>
            </a-col>

            <a-col :span="24">
                <div class="pos1-left-wrapper">
                    <div v-if="!showMobileCart" class="pos-left-header">
                        <PosLayout1
                            v-if="postLayout == 1"
                            :brands="brands"
                            :categories="categories"
                            :formData="formData"
                            @changed="reFetchProducts"
                        />
                        <PosLayout2
                            v-else
                            :brands="brands"
                            :categories="categories"
                            :formData="formData"
                            @changed="reFetchProducts"
                        />
                    </div>
                    <div v-if="!showMobileCart" class="pos-left-content">
                        <a-row
                            v-if="productLists.length > 0"
                            :gutter="30"
                            class="pos1-products-lists"
                        >
                            <a-col
                                v-for="item in productLists"
                                :key="item.xid"
                                :xxl="8"
                                :lg="8"
                                :md="8"
                                :sm="12"
                                :xs="12"
                                @click="selectSaleProduct(item)"
                            >
                                <ProductCardNew :product="item" />
                            </a-col>
                        </a-row>
                    </div>
                    <div v-if="showMobileCart">
                        <a-row class="mt-5 mb-5">
                            <a-col :xs="24" :sm="24" :md="24" :lg="24">
                                <a-table
                                    :row-key="(record) => record.xid"
                                    :dataSource="selectedProducts"
                                    :columns="orderItemColumns"
                                    :pagination="false"
                                    size="middle"
                                >
                                    <template #bodyCell="{ column, record }">
                                        <template v-if="column.dataIndex === 'name'">
                                            {{ record.name }} <br />
                                            <small
                                                v-if="record.product_type != 'service'"
                                            >
                                                <a-typography-text code>
                                                    {{ $t("product.avl_qty") }}
                                                    {{
                                                        `${record.stock_quantity}${record.unit_short_name}`
                                                    }}
                                                </a-typography-text>
                                            </small>
                                        </template>
                                           <template v-if="column.dataIndex === 'hsn_code'">
        {{ record.hsn_code || '-' }}
    </template>
                                        <template
                                            v-if="column.dataIndex === 'unit_quantity'"
                                        >
                                            <a-input-number
                                                id="inputNumber"
                                                v-model:value="record.quantity"
                                                :min="0"
                                                @change="quantityChanged(record)"
                                            />
                                        </template>
                               <template v-if="column.dataIndex === 'subtotal'">
    <div class="subtotal-cell">
        <div class="subtotal-main">
            {{ formatAmountCurrency(record.subtotal) }}
        </div>

        <div
            v-if="record.tax_rate !== null && record.tax_rate !== undefined && Number(record.tax_rate) > 0"
            class="subtotal-meta"
        >
          <a-tag color="gold" class="subtotal-tag">
    GST {{ record.tax_rate }}%
</a-tag>


        </div>
    </div>
</template>

                                        <template v-if="column.dataIndex === 'action'">
                                            <a-button
                                                type="primary"
                                                @click="editItem(record)"
                                                style="margin-left: 4px; margin-top: 4px"
                                            >
                                                <template #icon
                                                    ><EditOutlined
                                                /></template>
                                            </a-button>
                                            <a-button
                                                type="primary"
                                                @click="showDeleteConfirm(record)"
                                                style="margin-left: 4px; margin-top: 4px"
                                            >
                                                <template #icon
                                                    ><DeleteOutlined
                                                /></template>
                                            </a-button>
                                        </template>
                                    </template>
                                </a-table>
                            </a-col>
                        </a-row>
                    </div>
                    <div v-if="showMobileCart" class="pos-left-footer">
                        <a-card>
                            <div class="bill-footer" :style="{ paddingBotton: '30px' }">
                                <a-row :gutter="[16]">
                                    <a-col :xs="24" :sm="24" :md="8" :lg="8">
                                        <a-form-item :label="$t('stock.order_tax')">
                                            <a-select
                                                v-model:value="formData.tax_id"
                                                :placeholder="
                                                    $t('common.select_default_text', [
                                                        $t('stock.order_tax'),
                                                    ])
                                                "
                                                :allowClear="true"
                                                style="width: 100%"
                                                @change="taxChanged"
                                            >
                                                <a-select-option
                                                    v-for="tax in taxes"
                                                    :key="tax.xid"
                                                    :value="tax.xid"
                                                    :tax="tax"
                                                >
                                                    {{ tax.name }} ({{ tax.rate }}%)
                                                </a-select-option>
                                            </a-select>
                                        </a-form-item>
                                    </a-col>
                                    <a-col :xs="24" :sm="24" :md="8" :lg="8">
                                        <a-form-item :label="$t('stock.discount')">
                                            <a-input-group compact>
                                                <a-select
                                                    v-model:value="formData.discount_type"
                                                    @change="recalculateFinalTotal"
                                                    style="width: 30%"
                                                >
                                                    <a-select-option value="percentage">
                                                        %
                                                    </a-select-option>
                                                    <a-select-option value="fixed">
                                                        {{ appSetting.currency.symbol }}
                                                    </a-select-option>
                                                </a-select>
                                                <a-input-number
                                                    v-model:value="
                                                        formData.discount_value
                                                    "
                                                    :placeholder="
                                                        $t(
                                                            'common.placeholder_default_text',
                                                            [$t('stock.discount')]
                                                        )
                                                    "
                                                    @change="recalculateFinalTotal"
                                                    min="0"
                                                    style="width: 70%"
                                                />
                                            </a-input-group>
                                        </a-form-item>
                                    </a-col>
                                    <a-col :xs="24" :sm="24" :md="8" :lg="8">
                                        <a-form-item :label="$t('stock.shipping')">
                                            <a-input-number
                                                v-model:value="formData.shipping"
                                                :placeholder="
                                                    $t(
                                                        'common.placeholder_default_text',
                                                        [$t('stock.shipping')]
                                                    )
                                                "
                                                @change="recalculateFinalTotal"
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
                            </div>
                        </a-card>
                    </div>
                </div>
            </a-col>
        </a-row>
    </a-form>

    <div v-if="innerWidth <= 768" class="pos-mobile-footer">
        <a-row :gutter="16">
            <a-col :span="10">
                <a-row :gutter="16" :style="{ padding: '10px' }">
                    <a-col :span="24">
                        <span class="pos-grand-total">
                            {{ $t("common.total") }} :
                            {{ formatAmountCurrency(formData.subtotal) }}
                        </span>
                    </a-col>
                </a-row>
            </a-col>
            <a-col :span="14">
                <a-space :style="{ marginTop: '5px' }">
                    <a-button
                        v-if="showMobileCart"
                        @click="() => (showMobileCart = false)"
                        type="primary"
                    >
                        <template #icon>
                            <ShoppingCartOutlined />
                        </template>
                    </a-button>
                    <a-button
                        v-else
                        @click="() => (showMobileCart = true)"
                        type="primary"
                    >
                        <template #icon>
                            <UnorderedListOutlined />
                        </template>
                    </a-button>
                    <a-button
                        type="primary"
                        @click="payNow"
                        :disabled="
                            formData.subtotal <= 0 ||
                            formData.user_id == undefined ||
                            formData.user_id == '' ||
                            !formData.user_id
                        "
                    >
                        {{ $t("stock.pay_now") }}
                    </a-button>
                    <a-button @click="resetPos">
                        {{ $t("stock.reset") }}
                    </a-button>
                </a-space>
            </a-col>
        </a-row>
    </div>

    <a-modal
        :open="addEditVisible"
        :closable="false"
        :centered="true"
        :title="addEditPageTitle"
        @ok="onAddEditSubmit"
    >
        <a-form layout="vertical">
            <a-row :gutter="16">
                <a-col :xs="24" :sm="24" :md="24" :lg="24">
                    <a-form-item
                        :label="$t('product.unit_price')"
                        name="unit_price"
                        :help="
                            addEditRules.unit_price
                                ? addEditRules.unit_price.message
                                : null
                        "
                        :validateStatus="addEditRules.unit_price ? 'error' : null"
                    >
                        <a-input-number
                            v-model:value="addEditFormData.unit_price"
                            :placeholder="
                                $t('common.placeholder_default_text', [
                                    $t('product.unit_price'),
                                ])
                            "
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
            <a-row :gutter="16">
                <a-col :xs="24" :sm="24" :md="24" :lg="24">
                    <a-form-item
                        :label="$t('product.discount')"
                        name="discount_rate"
                        :help="
                            addEditRules.discount_rate
                                ? addEditRules.discount_rate.message
                                : null
                        "
                        :validateStatus="addEditRules.discount_rate ? 'error' : null"
                    >
                        <a-input-number
                            v-model:value="addEditFormData.discount_rate"
                            :placeholder="
                                $t('common.placeholder_default_text', [
                                    $t('product.discount'),
                                ])
                            "
                            min="0"
                            style="width: 100%"
                        >
                            <template #addonAfter>%</template>
                        </a-input-number>
                    </a-form-item>
                </a-col>
            </a-row>
            <a-row :gutter="16">
                <a-col :xs="24" :sm="24" :md="24" :lg="24">
                    <a-form-item
                        :label="$t('product.tax')"
                        name="tax_id"
                        :help="addEditRules.tax_id ? addEditRules.tax_id.message : null"
                        :validateStatus="addEditRules.tax_id ? 'error' : null"
                    >
                        <a-select
                            v-model:value="addEditFormData.tax_id"
                            :placeholder="
                                $t('common.select_default_text', [$t('product.tax')])
                            "
                            :allowClear="true"
                        >
                            <a-select-option
                                v-for="tax in taxes"
                                :key="tax.xid"
                                :value="tax.xid"
                            >
                                {{ tax.name }} ({{ tax.rate }}%)
                            </a-select-option>
                        </a-select>
                    </a-form-item>
                </a-col>
            </a-row>
            <a-row :gutter="16">
                <a-col :xs="24" :sm="24" :md="24" :lg="24">
                    <a-form-item
                        :label="$t('product.tax_type')"
                        name="tax_type"
                        :help="
                            addEditRules.tax_type ? addEditRules.tax_type.message : null
                        "
                        :validateStatus="addEditRules.tax_type ? 'error' : null"
                    >
                        <a-select
                            v-model:value="addEditFormData.tax_type"
                            :placeholder="
                                $t('common.select_default_text', [$t('product.tax_type')])
                            "
                            :allowClear="true"
                        >
                            <a-select-option
                                v-for="taxType in taxTypes"
                                :key="taxType.key"
                                :value="taxType.key"
                            >
                                {{ taxType.value }}
                            </a-select-option>
                        </a-select>
                    </a-form-item>
                </a-col>
            </a-row>
        </a-form>
        <template #footer>
            <a-button
                key="submit"
                type="primary"
                :loading="addEditFormSubmitting"
                @click="onAddEditSubmit"
            >
                <template #icon>
                    <SaveOutlined />
                </template>
                {{ $t("common.update") }}
            </a-button>
            <a-button key="back" @click="onAddEditClose">
                {{ $t("common.cancel") }}
            </a-button>
        </template>
    </a-modal>

    <PayNow
        :visible="payNowVisible"
        @closed="payNowClosed"
        @success="payNowSuccess"
        :data="formData"
        :selectedProducts="selectedProducts"
    />

    <InvoiceModal
        :visible="printInvoiceModalVisible"
        :order="printInvoiceOrder"
        @closed="printInvoiceModalVisible = false"
    />
</template>

<script>
import { ref, onMounted, reactive, toRefs, nextTick } from "vue";
import {
    ShoppingCartOutlined,
    PlusOutlined,
    EditOutlined,
    DeleteOutlined,
    SearchOutlined,
    SaveOutlined,
    SettingOutlined,
    UnorderedListOutlined,
} from "@ant-design/icons-vue";
import { debounce } from "lodash-es";
import { useI18n } from "vue-i18n";
import { message } from "ant-design-vue";
import { includes, find } from "lodash-es";
import common from "../../../../common/composable/common";
import { OrderSummary } from "../../../../common/components/product/style";
import fields from "./fields";
import ProductCardNew from "../../../../common/components/product/ProductCardNew.vue";
import PayNow from "./PayNow.vue";
import CustomerAddButton from "../../users/CustomerAddButton.vue";
import InvoiceModal from "./Invoice.vue";
import PosLayout1 from "./PosLayout1.vue";
import PosLayout2 from "./PosLayout2.vue";

export default {
    components: {
        PlusOutlined,
        SearchOutlined,
        EditOutlined,
        DeleteOutlined,
        SaveOutlined,
        SettingOutlined,
        ShoppingCartOutlined,
        UnorderedListOutlined,
        PosLayout1,
        PosLayout2,

        ProductCardNew,
        OrderSummary,
        PayNow,
        CustomerAddButton,
        InvoiceModal,
    },
    setup() {
        const {
            taxes,
            customers,
            brands,
            categories,
            productLists,
            orderItemColumns,
            formData,
            customerUrl,
            getPreFetchData,
            posDefaultCustomer,
        } = fields();
        console.log("customers",customers); // This assumes 'customers' is a reactive reference

        const selectedProducts = ref([]);
        const selectedProductIds = ref([]);
        const removedOrderItemsIds = ref([]);
        const postLayout = ref(1);

        const state = reactive({
            orderSearchTerm: undefined,
            productFetching: false,
            products: [],
        });
        const {
            formatAmount,
            formatAmountCurrency,
            appSetting,
            taxTypes,
            permsArray,
        } = common();
        const { t } = useI18n();


        // AddEdit
        const addEditVisible = ref(false);
        const addEditFormSubmitting = ref(false);
        const addEditFormData = ref({});
        const addEditRules = ref([]);
        const addEditPageTitle = ref("");

        // Pay Now
        const payNowVisible = ref(false);
        const printInvoiceModalVisible = ref(false);
        const printInvoiceOrder = ref({});

        // For mobile Design
        const showMobileCart = ref(false);
const handleCustomerChange = (selectedCustomerId) => {
    // If no customer selected (cleared), remove from storage
    if (!selectedCustomerId) {
        localStorage.removeItem('pos_selected_customer');
        formData.value.user_id = null;
        return;
    }

    // Find the selected customer
    const selectedCustomer = customers.value.find(
        customer => customer.xid === selectedCustomerId
    );

    if (selectedCustomer) {
        // Save only what you need
        const customerToSave = {
            xid: selectedCustomer.xid,
            name: selectedCustomer.name,
            phone: selectedCustomer.phone || '',
            email: selectedCustomer.email || '',
            address: selectedCustomer.address || ''
        };

        // Save to localStorage
        localStorage.setItem('pos_selected_customer', JSON.stringify(customerToSave));

        console.log('Customer saved:', customerToSave); // Optional: for debugging
    }

    // Always update the form
    formData.value.user_id = selectedCustomerId;
};
        // This will get customer data from localStorage when the page loads
        const getCustomerFromLocalStorage = () => {
    const storedCustomer = localStorage.getItem('selectedCustomer');
    if (storedCustomer) {
        const customer = JSON.parse(storedCustomer);
        formData.user_id = customer.xid; // Set the user ID
        // Optionally set more fields if necessary
        // For example:
        // formData.name = customer.name;
        // formData.phone = customer.phone;
    }
};

onMounted(async () => {
    await getPreFetchData();

    // Load the customer we actually saved
    const savedData = localStorage.getItem('pos_selected_customer');
    if (savedData) {
        try {
            const savedCustomer = JSON.parse(savedData);
            formData.value.user_id = savedCustomer.xid;
            console.log('Customer loaded from storage:', savedCustomer);
        } catch (e) {
            console.error('Failed to parse saved customer');
            localStorage.removeItem('pos_selected_customer');
        }
    }
});
        const reFetchProducts = () => {
            axiosAdmin
                .post("pos/products", {
                    brand_id: formData.value.brand_id,
                    category_id: formData.value.category_id,
                })
                .then((productResponse) => {
                    productLists.value = productResponse.data.products;
                });
        };

        const fetchProducts = debounce((value) => {
            fetchAllSearchedProduct(value);
        }, 300);

        const fetchAllSearchedProduct = (value) => {
            state.products = [];

            if (value != "") {
                state.productFetching = true;
                let url = `search-product`;

                axiosAdmin
                    .post(url, {
                        order_type: "sales",
                        search_term: value,
                        // products: selectedProductIds.value,
                    })
                    .then((response) => {
                        if (response.data.length == 1) {
                            searchValueSelected("", { product: response.data[0] });
                        } else {
                            state.products = response.data;
                        }

                        state.productFetching = false;
                    });
            }
        };

        const inputValueChanged = (keydownEvent) => {
            nextTick(() => {
                if (keydownEvent.keyCode == 13) {
                    fetchAllSearchedProduct(keydownEvent.target.value);
                }
            });
        };

        const searchValueSelected = (value, option) => {
            const newProduct = option.product;
            selectSaleProduct(newProduct);
        };

const selectSaleProduct = (newProduct) => {
    // ✅ Normalize HSN code once
    const resolvedHsnCode =
        newProduct.hsn_code ||
        newProduct.product?.hsn_code ||
        null;

    console.log("Selected product:", {
        xid: newProduct.xid,
        name: newProduct.name,
        subtotal: newProduct.subtotal,
        tax_rate: newProduct.tax_rate,
        hsn_code: resolvedHsnCode,
    });

    if (!includes(selectedProductIds.value, newProduct.xid)) {
        selectedProductIds.value.push(newProduct.xid);

        const baseLine = {
            ...newProduct,
            sn: selectedProducts.value.length + 1,
            quantity: newProduct.quantity || 1,
            discount_rate: newProduct.discount_rate || 0,
            unit_price: formatAmount(newProduct.unit_price),
            tax_rate: newProduct.tax_rate || 0,

            // ✅ attach normalized HSN here
            hsn_code: resolvedHsnCode,

            tax_type: "exclusive",
        };

        const calculatedLine = recalculateValues(baseLine);

        selectedProducts.value.push(calculatedLine);

        state.orderSearchTerm = undefined;
        state.products = [];
        recalculateFinalTotal();
    } else {
        const newProductSelection = find(selectedProducts.value, [
            "xid",
            newProduct.xid,
        ]);

        if (
            newProductSelection &&
            (newProductSelection.quantity < newProductSelection.stock_quantity ||
                newProductSelection.product_type == "service")
        ) {
            newProductSelection.quantity += 1;
            quantityChanged(newProductSelection);
        } else {
            message.error(t("common.out_of_stock"));
        }

        state.orderSearchTerm = undefined;
        state.products = [];
    }
};


    const recalculateValues = (product) => {
    let quantityValue = parseFloat(product.quantity);
    const maxQuantity = parseFloat(product.stock_quantity);
    const unitPrice = parseFloat(product.unit_price);

    // Clamp quantity to available stock (for non-service)
    if (product.product_type != "service") {
        quantityValue = quantityValue > maxQuantity ? maxQuantity : quantityValue;
    }

    // Discount
    const discountRate = product.discount_rate || 0;
    const totalDiscount = discountRate > 0 ? (discountRate / 100) * unitPrice : 0;
    const totalPriceAfterDiscount = unitPrice - totalDiscount;

    // Tax (always EXCLUSIVE)
    let taxAmount = 0;
    let subtotal = totalPriceAfterDiscount;
    let singleUnitPrice = unitPrice;

    if (product.tax_rate > 0) {
        taxAmount = totalPriceAfterDiscount * (product.tax_rate / 100);
        subtotal = totalPriceAfterDiscount + taxAmount;
        singleUnitPrice = totalPriceAfterDiscount;
    }

    const newObject = {
        ...product,
        total_discount: totalDiscount * quantityValue,
        subtotal: subtotal * quantityValue,
        quantity: quantityValue,
        total_tax: taxAmount * quantityValue,
        max_quantity: maxQuantity,
        single_unit_price: singleUnitPrice,

        // normalize every recalculated line as EXCLUSIVE
        tax_type: "exclusive",
    };

    return newObject;
};
const normalizeExistingLinesToExclusive = () => {
    if (!selectedProducts.value.length) return;

    selectedProducts.value = selectedProducts.value.map((p) =>
        recalculateValues({
            ...p,
            quantity: p.quantity || 1,
            discount_rate: p.discount_rate || 0,
            tax_type: "exclusive",
        })
    );

    recalculateFinalTotal();
};



        const quantityChanged = (record) => {
            const newResults = [];

            selectedProducts.value.map((selectedProduct) => {
                if (selectedProduct.xid == record.xid) {
                    const newValueCalculated = recalculateValues(record);
                    newResults.push(newValueCalculated);
                } else {
                    newResults.push(selectedProduct);
                }
            });
            selectedProducts.value = newResults;

            recalculateFinalTotal();
        };

        const recalculateFinalTotal = () => {
            let total = 0;
            selectedProducts.value.map((selectedProduct) => {
                total += selectedProduct.subtotal;
            });

            var discountAmount = 0;
            if (formData.value.discount_type == "percentage") {
                discountAmount =
                    formData.value.discount_value != ""
                        ? (parseFloat(formData.value.discount_value) * total) / 100
                        : 0;
            } else if (formData.value.discount_type == "fixed") {
                discountAmount =
                    formData.value.discount_value != ""
                        ? parseFloat(formData.value.discount_value)
                        : 0;
            }

            const taxRate =
                formData.value.tax_rate != "" ? parseFloat(formData.value.tax_rate) : 0;

            total = total - discountAmount;

            const tax = total * (taxRate / 100);

            total = total + parseFloat(formData.value.shipping);

            formData.value.subtotal = formatAmount(total + tax);
            formData.value.tax_amount = formatAmount(tax);
            formData.value.discount = discountAmount;
        };
const getRowSubtotalWithTax = (record) => {
    const base = Number(record.subtotal) || 0;
    const rate = Number(record.tax_rate) || 0;
    if (!rate) return base;

    return base + (base * rate) / 100;
};

const getRowTaxAmount = (record) => {
    const base = Number(record.subtotal) || 0;
    const rate = Number(record.tax_rate) || 0;
    if (!rate) return 0;

    return (base * rate) / 100;
};


        const showDeleteConfirm = (product) => {
            // Delete selected product and rearrange SN
            const newResults = [];
            let counter = 1;
            selectedProducts.value.map((selectedProduct) => {
                if (selectedProduct.item_id != null) {
                    removedOrderItemsIds.value = [
                        ...removedOrderItemsIds.value,
                        selectedProduct.item_id,
                    ];
                }

                if (selectedProduct.xid != product.xid) {
                    newResults.push({
                        ...selectedProduct,
                        sn: counter,
                        single_unit_price: formatAmount(
                            selectedProduct.single_unit_price
                        ),
                        tax_amount: formatAmount(selectedProduct.tax_amount),
                        subtotal: formatAmount(selectedProduct.subtotal),
                    });

                    counter++;
                }
            });
            selectedProducts.value = newResults;

            // Remove deleted product id from lists
            const filterProductIdArray = selectedProductIds.value.filter((newId) => {
                return newId != product.xid;
            });
            selectedProductIds.value = filterProductIdArray;
            recalculateFinalTotal();
        };

        const taxChanged = (value, option) => {
            formData.value.tax_rate = value == undefined ? 0 : option.tax.rate;
            recalculateFinalTotal();
        };

        // Edit a selected product
        const editItem = (product) => {
            addEditFormData.value = {
                id: product.xid,
                discount_rate: product.discount_rate,
                unit_price: product.unit_price,
                tax_id: product.x_tax_id,
                tax_type: product.tax_type == null ? undefined : product.tax_type,
            };
            addEditVisible.value = true;
            addEditPageTitle.value = product.name;
        };

        const payNow = () => {
            payNowVisible.value = true;
        };

        const payNowClosed = () => {
            payNowVisible.value = false;
        };

        const resetPos = () => {
            selectedProducts.value = [];
            selectedProductIds.value = [];

            formData.value = {
                ...formData.value,
                tax_id: undefined,
                category_id: undefined,
                brand_id: undefined,
                tax_id: undefined,
                tax_rate: 0,
                tax_amount: 0,
                discount_value: 0,
                discount: 0,
                shipping: 0,
                subtotal: 0,
            };

            recalculateFinalTotal();
        };

        // For Add Edit
        const onAddEditSubmit = () => {
            const record = selectedProducts.value.filter(
                (selectedProduct) => selectedProduct.xid == addEditFormData.value.id
            );

            const selecteTax = taxes.value.filter(
                (tax) => tax.xid == addEditFormData.value.tax_id
            );

            const taxType =
                addEditFormData.value.tax_type != undefined
                    ? addEditFormData.value.tax_type
                    : "exclusive";

            const newData = {
    ...record[0],
    discount_rate: parseFloat(addEditFormData.value.discount_rate),
    unit_price: parseFloat(addEditFormData.value.unit_price),
    tax_id: addEditFormData.value.tax_id,
    tax_rate: selecteTax[0] ? selecteTax[0].rate : 0,

    // 🔥 force exclusive on save too
    tax_type: "exclusive",
};

            quantityChanged(newData);
            onAddEditClose();
        };

        const onAddEditClose = () => {
            addEditFormData.value = {};
            addEditVisible.value = false;
        };

        // Customer
        const customerAdded = () => {
            axiosAdmin.get(customerUrl).then((response) => {
                customers.value = response.data;
            });
        };

        const payNowSuccess = (invoiceOrder) => {
            resetPos();

            var walkInCustomerId =
                posDefaultCustomer.value && posDefaultCustomer.value.xid
                    ? posDefaultCustomer.value.xid
                    : undefined;
            formData.value = {
                ...formData.value,
                user_id: walkInCustomerId,
            };

            reFetchProducts();
            payNowVisible.value = false;

            printInvoiceOrder.value = invoiceOrder;
            printInvoiceModalVisible.value = true;
        };

        return {
            taxes,
            customers,
            categories,
            brands,
            productLists,
            formData,
            reFetchProducts,
            selectSaleProduct,

            taxChanged,
            quantityChanged,
            recalculateFinalTotal,
handleCustomerChange,
            // Pay Now
            payNow,
            payNowVisible,
            payNowClosed,
            resetPos,

            appSetting,
            permsArray,
            ...toRefs(state),
            fetchProducts,
            searchValueSelected,
            selectedProducts,
            orderItemColumns,
            formatAmount,
            formatAmountCurrency,

            containerStyle: {
                height: window.innerHeight - 110 + "px",
                overflow: "scroll",
                "overflow-y": "scroll",
            },

            customerAdded,

            // Add Edit
            editItem,
            addEditVisible,
            addEditFormData,
            addEditFormSubmitting,
            addEditRules,
            addEditPageTitle,
            onAddEditSubmit,
            onAddEditClose,
            taxTypes,
            showDeleteConfirm,

            payNowSuccess,

            printInvoiceModalVisible,
            printInvoiceOrder,

            postLayout,
            innerWidth: window.innerWidth,

            inputValueChanged,

            showMobileCart,
getRowSubtotalWithTax,
    getRowTaxAmount,        };
    },
};
</script>

<style lang="less">
.right-pos-sidebar .ps {
    height: calc(100vh - 90px);
}

.right-icon {
    width: 15%;
    border: 1px solid #d9d9d9;
    border-left: 0px;
    height: 32px;

    span {
        padding-left: 14px;
        padding-top: 7px;
    }
}

.bill-body {
    height: 100%;
}

.bill-table {
    height: 100%;
}

.left-pos-top {
    .ant-card-body {
        padding-bottom: 0px;
    }
}

.left-pos-middle-table {
    height: calc(100vh - 420px);
    overflow-y: overlay;

    .ant-card-body {
        padding-bottom: 0px;
        padding-top: 0px;
    }
}

.pos-left-wrapper {
    display: flex;
    flex-direction: column;
}

.pos-left-content {
    flex: 1;
    // overflow: auto;
}

.pos-left-footer {
    .ant-card-body {
        padding-bottom: 0px;
    }
}

.pos-grand-total {
    font-size: 18px;
    font-weight: bold;
}

.pos1-left-wrapper {
    display: flex;
    flex-direction: column;
}

.pos1-left-content {
    flex: 1;
    overflow: auto;
}

.pos1-left-footer {
    .ant-card-body {
        padding-bottom: 0px;
    }
}

.pos-grand-total {
    font-size: 18px;
    font-weight: bold;
}

.pos1-products-lists {
    height: calc(100vh - 245px);
    overflow-y: overlay;

    img {
        height: 100px;
    }

    .product-pos {
        margin-top: 5px;
    }
}

.left-pos1-middle-table {
    height: calc(100vh - 500px);
    overflow-y: overlay;

    .ant-card-body {
        padding-bottom: 0px;
        padding-top: 0px;
    }
}

.pos1-bill-filters {
    .ant-card-body {
        padding: 10px 3px;
    }
}

.pos-mobile-footer {
    position: fixed;
    left: 0;
    bottom: 0;
    width: 100%;
    background-color: white;
    text-align: center;
    border-top: 1px solid #e8e8e8;
}
.subtotal-cell {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.subtotal-main {
    font-weight: 500;
}

.subtotal-meta {
    font-size: 11px;
    color: #666;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 4px;
}

.subtotal-tag {
    border-radius: 999px;
    padding: 0 6px;
}

</style>
