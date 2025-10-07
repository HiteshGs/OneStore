<template>
  <AdminPageHeader>
    <template #header>
      <a-page-header :title="$t(`menu.print_barcodes`)" class="p-0" />
    </template>

    <template #breadcrumb>
      <a-breadcrumb separator="-" style="font-size: 12px">
        <a-breadcrumb-item>
          <router-link :to="{ name: 'admin.dashboard.index' }">
            {{ $t(`menu.dashboard`) }}
          </router-link>
        </a-breadcrumb-item>
        <a-breadcrumb-item>
          {{ $t(`menu.print_barcodes`) }}
        </a-breadcrumb-item>
      </a-breadcrumb>
    </template>
  </AdminPageHeader>

  <admin-page-table-content>
    <a-card class="page-content-container mt-20 mb-20">
      <a-form layout="vertical">
        <a-row :gutter="16" class="mb-20">
          <a-col :xs="24" :sm="24" :md="12" :lg="24" :xl="24">
            <ProductSearchInput @valueChanged="productSelected" />
          </a-col>
        </a-row>

        <a-row :gutter="16">
          <a-col :xs="24">
            <a-table
              :row-key="(record) => record.xid"
              :dataSource="selectedProducts"
              :columns="orderItemColumns"
              :pagination="false"
            >
              <template #bodyCell="{ column, record }">
                <template v-if="column.dataIndex === 'name'">
                  {{ record.name }}
                </template>

                <template v-if="column.dataIndex === 'unit_quantity'">
                  <a-input-number
                    v-model:value="record.quantity"
                    :min="1"
                    @change="quantityChanged(record)"
                  />
                </template>

                <template v-if="column.dataIndex === 'action'">
                  <a-button type="primary" @click="showDeleteConfirm(record)">
                    <template #icon><DeleteOutlined /></template>
                  </a-button>
                </template>
              </template>
            </a-table>
          </a-col>
        </a-row>

        <a-row :gutter="16" class="mt-20" align="middle">
          <a-col :xs="24" :sm="12" :md="6" :lg="6">
            <a-checkbox v-model:checked="selectName">
              {{ $t("print_barcode.select_name") }}
            </a-checkbox>
          </a-col>

          <a-col :xs="24" :sm="12" :md="6" :lg="6">
            <a-checkbox v-model:checked="selectPrice">
              {{ $t("print_barcode.select_price") }}
            </a-checkbox>
          </a-col>

          <a-col :xs="24" :sm="12" :md="6" :lg="6">
            <a-checkbox v-model:checked="fitLastPage">
              Fit last page (no wasted paper)
            </a-checkbox>
          </a-col>

          <a-col :xs="24" :sm="12" :md="6" :lg="6">
            <a-checkbox v-model:checked="centerLastPage">
              Center last page (preview/print)
            </a-checkbox>
          </a-col>
        </a-row>

        <a-row :gutter="16" class="mt-20" align="middle">
          <a-col :xs="24" :sm="12" :md="8" :lg="8">
            <a-form-item :label="$t('print_barcode.paper_size')" name="paper_size">
              <a-select
                v-model:value="perSheetBarcode"
                :placeholder="$t('common.select_default_text', [$t('print_barcode.paper_size')])"
                @change="rebuildPages"
              >
                <a-select-option v-for="opt in paperSizeArray" :key="`size-${opt.value}`" :value="opt.value">
                  {{ opt.label }}
                </a-select-option>

                <!-- Custom 1–9 -->
                <a-select-option value="custom">Custom (1–9 per page)</a-select-option>

                <!-- NEW: QR layouts -->
                <a-select-option value="qr1">QR — 1 big per page</a-select-option>
                <a-select-option value="qr2">QR — 2 big per page</a-select-option>
              </a-select>
            </a-form-item>
          </a-col>

          <!-- Custom count input -->
          <a-col :xs="24" :sm="12" :md="8" :lg="8" v-if="perSheetBarcode === 'custom'">
            <a-form-item label="Custom count (1–9)">
              <a-input-number v-model:value="customPerPage" :min="1" :max="9" @change="rebuildPages" />
            </a-form-item>
          </a-col>

          <a-col :xs="24" :sm="24" :md="8" :lg="8">
            <a-form-item label="Label gap">
              <a-select v-model:value="gapMode" @change="rebuildPages">
                <a-select-option value="tight">Tight</a-select-option>
                <a-select-option value="normal">Normal</a-select-option>
                <a-select-option value="loose">Loose</a-select-option>
              </a-select>
            </a-form-item>
          </a-col>
        </a-row>

        <a-row :gutter="16" align="middle">
          <a-col :xs="24" :sm="24" :md="12" :lg="12">
            <div class="hint">Print tip: set <b>Margins → None</b> and uncheck <b>Headers & footers</b>.</div>
          </a-col>
          <a-col :xs="24" :sm="24" :md="12" :lg="12">
            <a-form-item label="Preview zoom (screen only)" class="m-0">
              <a-slider :min="80" :max="150" :step="5" v-model:value="previewScale" />
            </a-form-item>
          </a-col>
        </a-row>
      </a-form>

      <div class="mt-16 mb-20">
        <a-space :size="12">
          <a-button style="color:#fff;background:#e81515" @click="reset">
            {{ $t("common.reset") }}
            <template #icon><LinkOutlined /></template>
          </a-button>

          <a-button type="primary" @click="printBarcodes">
            <template #icon><PrinterOutlined /></template>
            {{ $t("product.print_barcode") }}
          </a-button>
        </a-space>
      </div>

      <!-- PREVIEW / PRINT -->
      <div
        class="mt-30 preview-wrapper"
        :style="{ transform: `scale(${previewScale/100})`, transformOrigin: 'top left' }"
        ref="contentToPrint"
      >
        <div
          class="give-border print-page"
          v-for="(page, pageIndex) in pages"
          :key="`page-${pageIndex}`"
          :style="pageIndex === pages.length - 1 ? lastPageStyleObject : pageStyleObject"
        >
          <div class="grid" :class="{ 'qr-grid': isQRLayout }" :style="gridStyleObject(pageIndex)">
            <div
              v-for="(bc, idx) in page"
              :key="`bc-${pageIndex}-${idx}`"
              class="cell"
              :class="{ 'qr-cell': isQRLayout }"
              :style="cellStyleObject"
            >
              <div class="label-inner" :class="{ 'qr-inner': isQRLayout }">
                <div v-if="selectName" class="label-name" :class="{ 'qr-name': isQRLayout }">
                  {{ bc.name }}
                </div>

                <!-- QR STYLE BARCODE -->
                <BarcodeGenerator
                  :value="String(bc.item_code || '')"
                  :format="isQRLayout ? 'qrcode' : bc.barcode_symbology"
                  :height="isQRLayout ? 220 : 18"
                  :width="isQRLayout ? 2 : 1"
                  :fontSize="isQRLayout ? 0 : 16"
                  :elementTag="'svg'"
                />

                <div v-if="selectPrice && bc.price !== ''" class="label-price" :class="{ 'qr-price': isQRLayout }">
                  {{ formatAmountCurrency(bc.price) }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <a-empty v-if="selectedProducts.length === 0" class="mt-20" />
    </a-card>
  </admin-page-table-content>
</template>

<script>
import { ref, createVNode, watch, nextTick, computed } from "vue";
import {
  PrinterOutlined,
  LinkOutlined,
  DeleteOutlined,
  ExclamationCircleOutlined,
} from "@ant-design/icons-vue";
import { useI18n } from "vue-i18n";
import { Modal } from "ant-design-vue";
import common from "../../../../common/composable/common";
import AdminPageHeader from "../../../../common/layouts/AdminPageHeader.vue";
import ProductSearchInput from "./ProductSearchInput.vue";
import BarcodeGenerator from "../../../../common/components/barcode/BarcodeGenerator.vue";
import { find } from "lodash-es";

export default {
  components: {
    PrinterOutlined,
    LinkOutlined,
    DeleteOutlined,
    ExclamationCircleOutlined,
    AdminPageHeader,
    ProductSearchInput,
    BarcodeGenerator,
  },
  setup() {
    const { formatAmountCurrency } = common();
    const { t } = useI18n();

    // Built-in linear layouts (strict grid, inches)
    const LAYOUTS = {
      40: { rows: 10, cols: 4, w: 1.799, h: 1.003, a4: true },
      30: { rows: 10, cols: 3, w: 2.625, h: 1.0,   a4: false },
      24: { rows: 8,  cols: 3, w: 2.48,  h: 1.334, a4: true },
      20: { rows: 10, cols: 2, w: 4.0,   h: 1.0,   a4: false },
      18: { rows: 6,  cols: 3, w: 2.5,   h: 1.835, a4: true },
      14: { rows: 7,  cols: 2, w: 4.0,   h: 1.33,  a4: false },
      12: { rows: 4,  cols: 3, w: 2.5,   h: 2.834, a4: true },
      10: { rows: 5,  cols: 2, w: 4.0,   h: 2.0,   a4: false },
    };

    const paperSizeArray = ref([
      { label: "40 per sheet (A4) (1.799 × 1.003 in)", value: 40 },
      { label: "30 per sheet (2.625 × 1 in)", value: 30 },
      { label: "24 per sheet (A4) (2.48 × 1.334 in)", value: 24 },
      { label: "20 per sheet (4 × 1 in)", value: 20 },
      { label: "18 per sheet (A4) (2.5 × 1.835 in)", value: 18 },
      { label: "14 per sheet (4 × 1.33 in)", value: 14 },
      { label: "12 per sheet (A4) (2.5 × 2.834 in)", value: 12 },
      { label: "10 per sheet (4 × 2 in)", value: 10 },
    ]);

    // State
    const selectedProducts = ref([]);
    const perSheetBarcode = ref(40);
    const customPerPage = ref(6); // for "custom" mode (1–9)
    const A4_PAGE = { widthIn: 8.27, heightIn: 11.69, padIn: 0.10 };

    const selectName = ref(false);
    const selectPrice = ref(false);
    const fitLastPage = ref(true);
    const centerLastPage = ref(true);
    const previewScale = ref(100);
    const gapMode = ref("normal");

    const pages = ref([]); // array of arrays of label objects
    const pageStyleObject = ref({});
    const lastPageStyleObject = ref({});
    const cellStyleObject = ref({});
    const contentToPrint = ref(null);

    const orderItemColumns = [
      { title: "#", dataIndex: "sn" },
      { title: t("print_barcode.name"), dataIndex: "name" },
      { title: t("print_barcode.quantity"), dataIndex: "unit_quantity" },
      { title: t("common.action"), dataIndex: "action" },
    ];

    const inch = (n) => `${n}in`;
    const isQRLayout = computed(() => perSheetBarcode.value === "qr1" || perSheetBarcode.value === "qr2");

    // Dynamic grid style (supports linear, custom, and QR)
    const gridStyleObject = (pageIndex) => {
      const gapX = gapMode.value === "tight" ? 0.04 : gapMode.value === "loose" ? 0.12 : 0.07;
      const gapY = 0.06;

      // QR layouts: big squares, 1×1 or 2×1 (stack)
      if (perSheetBarcode.value === "qr1" || perSheetBarcode.value === "qr2") {
        const cols = 1;
        const rows = perSheetBarcode.value === "qr2" ? 2 : 1;

        const usableW = A4_PAGE.widthIn - 2 * A4_PAGE.padIn - (cols - 1) * gapX;
        const usableH = A4_PAGE.heightIn - 2 * A4_PAGE.padIn - (rows - 1) * gapY;

        // each cell should be square; take the limiting dimension
        const cellSide = Math.min(usableW / cols, usableH / rows);

        const base = {
          display: "grid",
          gridTemplateColumns: `repeat(${cols}, ${inch(cellSide)})`,
          gridAutoRows: inch(cellSide),
          columnGap: inch(gapX),
          rowGap: inch(gapY),
          alignContent: "start",
          justifyContent: centerLastPage.value && pageIndex === pages.value.length - 1 ? "center" : "start",
        };
        return base;
      }

      // Custom 1–9
      if (perSheetBarcode.value === "custom") {
        const n = Math.min(9, Math.max(1, Number(customPerPage.value || 1)));
        const cols = Math.min(3, n);
        const rows = Math.ceil(n / cols);
        const usableW = A4_PAGE.widthIn - 2 * A4_PAGE.padIn - (cols - 1) * gapX;
        const usableH = A4_PAGE.heightIn - 2 * A4_PAGE.padIn - (rows - 1) * gapY;
        const cellW = usableW / cols;
        const cellH = usableH / rows;

        return {
          display: "grid",
          gridTemplateColumns: `repeat(${cols}, ${inch(cellW)})`,
          gridAutoRows: inch(cellH),
          columnGap: inch(gapX),
          rowGap: inch(gapY),
          alignContent: "start",
          justifyContent: centerLastPage.value && pageIndex === pages.value.length - 1 ? "center" : "start",
        };
      }

      // Linear templates
      const L = LAYOUTS[perSheetBarcode.value] || LAYOUTS[40];
      return {
        display: "grid",
        gridTemplateColumns: `repeat(${L.cols}, ${inch(L.w)})`,
        gridAutoRows: inch(L.h),
        columnGap: inch(gapX),
        rowGap: inch(gapY),
        alignContent: "start",
        justifyContent: centerLastPage.value && pageIndex === pages.value.length - 1 ? "center" : "start",
      };
    };

    const rebuildStyles = () => {
      const isA4 =
        perSheetBarcode.value === "custom" ||
        perSheetBarcode.value === "qr1" ||
        perSheetBarcode.value === "qr2"
          ? true
          : !!(LAYOUTS[perSheetBarcode.value] || LAYOUTS[40]).a4;

      const fullW = isA4 ? "8.27in" : "8.5in";
      const fullH = isA4 ? "11.69in" : "11in";

      pageStyleObject.value = {
        width: fullW,
        height: fullH,
        margin: "10px auto",
        padding: inch(A4_PAGE.padIn),
        boxSizing: "border-box",
        pageBreakAfter: "always",
        background: "#fff",
        border: "1px solid #ddd",
      };

      lastPageStyleObject.value = {
        ...pageStyleObject.value,
        height: fitLastPage.value ? "auto" : fullH,
        minHeight: fitLastPage.value ? "unset" : fullH,
        pageBreakAfter: "auto",
      };

      cellStyleObject.value = isQRLayout.value
        ? {
            border: "2px solid #000",
            borderRadius: "6px",
            padding: "10mm 10mm 8mm 10mm", // generous white space around QR
            background: "#fff",
            display: "flex",
            alignItems: "center",
            justifyContent: "center",
            textAlign: "center",
          }
        : {
            border: "1px dotted #ccc",
            textTransform: "uppercase",
            fontSize: "12px",
            lineHeight: "14px",
            overflow: "hidden",
            textAlign: "center",
            display: "flex",
            alignItems: "center",
            justifyContent: "center",
          };
    };

    const rebuildPages = () => {
      rebuildStyles();

      const list = [];
      selectedProducts.value.forEach((p) => {
        const q = Math.max(0, Number(p.quantity || 0));
        for (let i = 0; i < q; i++) {
          list.push({
            xid: p.xid,
            name: p.name,
            item_code: p.item_code,
            barcode_symbology: p.barcode_symbology,
            price: p.price,
          });
        }
      });

      let pageSize;
      if (perSheetBarcode.value === "custom") {
        pageSize = Math.min(9, Math.max(1, Number(customPerPage.value || 1)));
      } else if (perSheetBarcode.value === "qr1") {
        pageSize = 1;
      } else if (perSheetBarcode.value === "qr2") {
        pageSize = 2;
      } else {
        pageSize = Number(perSheetBarcode.value) || 40;
      }

      const out = [];
      for (let i = 0; i < list.length; i += pageSize) {
        out.push(list.slice(i, i + pageSize));
      }
      pages.value = out.length ? out : [[]];
    };

    const quantityChanged = (record) => {
      const row = selectedProducts.value.find((x) => x.xid === record.xid);
      if (row) row.quantity = record.quantity;
      rebuildPages();
    };

    const showDeleteConfirm = (product) => {
      Modal.confirm({
        title: t("common.delete") + "?",
        icon: createVNode(ExclamationCircleOutlined),
        content: t("print_barcode.delete_message"),
        centered: true,
        okText: t("common.yes"),
        okType: "danger",
        cancelText: t("common.no"),
        onOk() {
          selectedProducts.value = selectedProducts.value
            .filter((x) => x.xid !== product.xid)
            .map((x, i) => ({ ...x, sn: i + 1 }));
          rebuildPages();
        },
      });
    };

    const productSelected = ({ product }) => {
      const exists = find(selectedProducts.value, { xid: product.xid });
      if (!exists) {
        selectedProducts.value.push({
          sn: selectedProducts.value.length + 1,
          xid: product.xid,
          name: product.name,
          item_code: product.item_code,
          price: product?.details?.sales_price || "",
          quantity: 10,
          barcode_symbology: product.barcode_symbology,
        });
      }
      rebuildPages();
    };

    const reset = () => {
      selectedProducts.value = [];
      pages.value = [[]];
      rebuildStyles();
    };

    // Robust iframe print
    const printBarcodes = async () => {
      await nextTick();
      const node = contentToPrint.value;
      const htmlBody = node?.innerHTML?.trim();
      if (!htmlBody) return;

      const iframe = document.createElement("iframe");
      Object.assign(iframe.style, {
        position: "fixed", right: "0", bottom: "0", width: "0", height: "0", border: "0",
      });
      iframe.setAttribute("aria-hidden", "true");
      document.body.appendChild(iframe);

      const doc = iframe.contentDocument || iframe.contentWindow.document;

      const PRINT_CSS = `
        @page { size: A4; margin: 8mm; }
        * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { font-family: Arial, Helvetica, sans-serif; margin: 0; }
        .print-page { page-break-after: always; }
        .print-page:last-child { page-break-after: auto; }
        .grid { display: grid; }
        .cell { display:flex; align-items:center; justify-content:center; text-align:center; background:#fff; }
        .give-border { border: 1px solid #ccc; }
        .label-inner { font-weight: bold; }
        .label-name, .label-price { text-align: center; }
        /* QR emphasis */
        .qr-grid { }
        .qr-cell { border: 2px solid #000; border-radius: 6px; padding: 10mm 10mm 8mm 10mm; }
        .qr-inner { }
        .qr-name { font-size: 16px; margin-bottom: 6mm; }
        .qr-price { font-size: 16px; margin-top: 6mm; }
        svg { max-width: 100%; height: auto; }
      `;

      doc.open();
      doc.write(`<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Print Barcodes</title>
  <style>${PRINT_CSS}</style>
</head>
<body>${htmlBody}</body>
</html>`);
      doc.close();

      const doPrint = () => {
        try { iframe.contentWindow.focus(); iframe.contentWindow.print(); }
        finally {
          setTimeout(() => document.body.contains(iframe) && document.body.removeChild(iframe), 600);
        }
      };

      iframe.onload = () => setTimeout(doPrint, 120);
      setTimeout(() => document.body.contains(iframe) && doPrint(), 500);
    };

    // Recompute on controls
    watch([fitLastPage, gapMode, perSheetBarcode, customPerPage], () => rebuildPages());

    // init
    rebuildStyles();
    pages.value = [[]];

    return {
      // i18n / formatting
      t, formatAmountCurrency,

      // table/data
      orderItemColumns, selectedProducts,

      // controls
      perSheetBarcode, paperSizeArray,
      customPerPage,
      selectName, selectPrice,
      fitLastPage, centerLastPage,
      previewScale, gapMode,

      // computed flags
      isQRLayout,

      // preview / print
      pages,
      pageStyleObject, lastPageStyleObject,
      gridStyleObject, cellStyleObject,
      contentToPrint,

      // actions
      productSelected, quantityChanged,
      showDeleteConfirm, reset,
      rebuildPages, printBarcodes,
    };
  },
};
</script>

<style lang="less" scoped>
td { border: 1px dotted lightgray; }

/* screen preview */
.preview-wrapper { width: 900px; }

.give-border { border: 1px solid #ccc; }
.label-name, .label-price { text-align: center; }
.hint { color:#666; font-size:12px; }

/* QR specific (screen) */
.qr-cell { border: 2px solid #000; border-radius: 6px; padding: 10mm 10mm 8mm 10mm; }
.qr-name { font-size: 16px; margin-bottom: 6mm; }
.qr-price { font-size: 16px; margin-top: 6mm; }

@media print {
  table { page-break-after: always; }
  .print-page { page-break-after: always; border: none; }
  .print-page:last-child { page-break-after: auto; }
}
</style>
