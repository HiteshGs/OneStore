<template>
  <a-modal
    :open="visible"
    :centered="true"
    :maskClosable="false"
    :title="$t('common.print_invoice')"
    width="900px"
    @cancel="onClose"
  >
    <div id="pos-invoice">
      <div
        id="pos-invoice-inner"
        :class="['invoice-root', sizeClass]"
        v-if="order && order.xid"
        style="margin: 0 auto"
      >
        <!-- TOP HEADER (STORE INFO) -->
        <div class="invoice-header">
          <img
            class="invoice-logo"
            :src="selectedWarehouse.logo_url"
            :alt="selectedWarehouse.name"
          />
          <div class="invoice-header-text">
            <h1 class="store-name">
              {{ selectedWarehouse.name }}
            </h1>
            <p class="store-address">
              {{ selectedWarehouse.address }}
            </p>
            <p class="store-contact">
              <span v-if="selectedWarehouse.phone">
                {{ $t("common.phone") }}: {{ selectedWarehouse.phone }}
              </span>
              <span v-if="selectedWarehouse.email">
                &nbsp;|&nbsp;{{ $t("common.email") }}: {{ selectedWarehouse.email }}
              </span>
              <!-- GSTIN with static fallback -->
              <span class="store-gst">
                &nbsp;|&nbsp;GSTIN: {{ warehouseGstNumber }}
              </span>
            </p>
          </div>
        </div>

        <!-- TAX INVOICE + META (LIKE PDF TOP BOX) -->
        <div class="invoice-meta-row">
          <div class="invoice-meta-left">
            <h2 class="tax-invoice-title">
              {{ $t("sales.tax_invoice") }}
            </h2>
          </div>
          <div class="invoice-meta-right">
            <table class="invoice-meta-table">
              <tbody>
                <tr>
                  <td class="meta-label">
                    {{ $t("sales.invoice") }}
                  </td>
                  <td class="meta-value">
                    : {{ order.invoice_number }}
                  </td>
                </tr>
                <tr>
                  <td class="meta-label">
                    {{ $t("common.date") }}
                  </td>
                  <td class="meta-value">
                    : {{ formatDate(order.order_date) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- BILL TO (LIKE "BILL TO PARTY" BLOCK) -->
        <div class="bill-party-row">
          <div class="bill-party-left">
            <h3 class="bill-title">
              Bill To Party
            </h3>
            <p class="party-line party-name">
              {{ order.user?.name }}
            </p>
            <p v-if="order.user?.phone" class="party-line">
              Mo : {{ order.user.phone }}
            </p>
            <p v-if="order.user?.alt_phone" class="party-line">
              {{ order.user.alt_phone }}
            </p>
            <p v-if="order.user?.gst_number" class="party-line">
              GSTN : {{ order.user.gst_number }}
            </p>
            <p v-if="order.user?.address" class="party-line">
              {{ order.user.address }}
            </p>
            <p v-if="order.user?.city" class="party-line">
              {{ order.user.city }}
            </p>
          </div>

          <div class="bill-party-right">
            <div v-if="selectedWarehouse.city" class="party-line">
              {{ selectedWarehouse.city }}
            </div>
            <div v-if="selectedWarehouse.state" class="party-line">
              {{ selectedWarehouse.state }}
            </div>
          </div>
        </div>

        <!-- ITEMS TABLE (LIKE PDF: NO / PARTICULAR / HSN / UNIT / QTY / RATE / TAXABLE / GST% / TOTAL) -->
        <div class="tax-invoice-items">
          <table class="items-table">
            <thead>
              <tr>
                <th style="width: 5%">NO</th>
                <th style="width: 25%">{{ $t("common.item") }}</th>
                <th style="width: 10%">{{ $t("product.hsn") || "HSN NO" }}</th>
                <th style="width: 8%">{{ $t("product.unit") }}</th>
                <th style="width: 8%">{{ $t("common.qty") }}</th>
                <th style="width: 12%">{{ $t("common.rate") }}</th>
                <th style="width: 14%">
                  {{ $t("invoice.taxable_amount") || "Taxable Amount" }}
                </th>
                <th style="width: 8%">{{ $t("product.tax") }} %</th>
                <th style="width: 10%; text-align: right">
                  {{ $t("common.total") }}
                </th>
              </tr>
            </thead>
            <tbody>
              <tr
                class="item-row"
                v-for="(item, index) in order.items"
                :key="item.xid"
              >
                <td class="center">
                  {{ index + 1 }}
                </td>

                <!-- Item name + custom fields from product_custom_fields -->
                <td>
                  <div class="item-name">
                    {{ item.product?.name }}
                  </div>
                  <div
                    v-if="getItemCustomFields(item).length"
                    class="item-custom-fields"
                  >
                    <span
                      v-for="cf in getItemCustomFields(item)"
                      :key="cf.id || cf.field_name"
                      class="item-custom-field-line"
                    >
                      {{ cf.field_name }}: {{ cf.field_value }}
                    </span>
                  </div>
                </td>

                <!-- HSN NO (from item/product/custom_fields) -->
                <td class="center">
                  {{ getItemHsnCode(item) }}
                </td>

                <!-- UNIT -->
                <td class="center">
                  {{ item.unit?.short_name || item.unit?.name || "-" }}
                </td>

                <!-- QTY -->
                <td class="center">
                  {{ item.quantity }}
                </td>

                <!-- RATE -->
                <td class="right">
                  {{ formatAmountCurrency(item.unit_price) }}
                </td>

                <!-- TAXABLE AMOUNT = subtotal - tax -->
                <td class="right">
                  {{ formatAmountCurrency(getTaxableAmount(item)) }}
                </td>

                <!-- GST % -->
                <td class="center">
                  <span v-if="getTaxRate(item)">
                    {{ getTaxRate(item) }}%
                  </span>
                  <span v-else>-</span>
                </td>

                <!-- TOTAL (WITH TAX) -->
                <td class="right">
                  {{ formatAmountCurrency(item.subtotal) }}
                </td>
              </tr>

              <!-- ORDER TAX / DISCOUNT / SHIPPING LINES UNDER TABLE -->
              <tr class="item-row-other">
                <td colspan="7" class="right">
                  {{ $t("stock.order_tax") }}
                </td>
                <td colspan="2" class="right">
                  {{ formatAmountCurrency(order.tax_amount) }}
                </td>
              </tr>

              <tr class="item-row-other">
                <td colspan="7" class="right">
                  {{ $t("stock.discount") }}
                </td>
                <td colspan="2" class="right">
                  {{ formatAmountCurrency(order.discount) }}
                </td>
              </tr>

              <tr class="item-row-other">
                <td colspan="7" class="right">
                  {{ $t("stock.shipping") }}
                </td>
                <td colspan="2" class="right">
                  {{ formatAmountCurrency(order.shipping) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- TOTALS LIKE PDF (GROSS / SGST / CGST / IGST / NET) -->
        <div class="tax-invoice-totals">
          <table style="width: 100%">
            <tbody>
              <tr>
                <td style="width: 50%">
                  <div class="amount-summary-block">
                    <p>
                      <strong>GROSS AMT :</strong>
                      {{ formatAmountCurrency(getGrossAmount(order)) }}
                    </p>
                    <p>
                      <strong>SGST :</strong>
                      {{ formatAmountCurrency(order.sgst_amount || 0) }}
                    </p>
                    <p>
                      <strong>CGST :</strong>
                      {{ formatAmountCurrency(order.cgst_amount || 0) }}
                    </p>
                    <p>
                      <strong>IGST :</strong>
                      {{ formatAmountCurrency(order.igst_amount || 0) }}
                    </p>
                    <p>
                      <strong>NET AMOUNT :</strong>
                      {{ formatAmountCurrency(order.total) }}
                    </p>
                    <p v-if="order.amount_in_words" class="amount-words">
                      Rupees (in words) :- {{ order.amount_in_words }}
                    </p>
                  </div>
                </td>
                <td style="width: 50%">
                  <!-- SIMPLE GST SLAB SUMMARY BOX -->
                  <div class="gst-summary-block">
                    <table>
                      <thead>
                        <tr>
                          <th>GST(%)</th>
                          <th class="right">Taxable</th>
                          <th class="right">GST</th>
                          <th class="right">Net</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr
                          v-for="row in gstSummary"
                          :key="row.rate"
                        >
                          <td class="center">{{ row.rate }}%</td>
                          <td class="right">
                            {{ formatAmountCurrency(row.taxable) }}
                          </td>
                          <td class="right">
                            {{ formatAmountCurrency(row.taxAmount) }}
                          </td>
                          <td class="right">
                            {{ formatAmountCurrency(row.net) }}
                          </td>
                        </tr>
                        <tr class="gst-summary-total">
                          <td class="center">{{ $t("common.total") }}</td>
                          <td class="right">
                            {{ formatAmountCurrency(gstSummaryTotals.taxable) }}
                          </td>
                          <td class="right">
                            {{ formatAmountCurrency(gstSummaryTotals.taxAmount) }}
                          </td>
                          <td class="right">
                            {{ formatAmountCurrency(gstSummaryTotals.net) }}
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- PAID / DUE (LIKE PDF BOTTOM BOX) -->
        <div class="paid-amount-deatils">
          <table style="width: 100%">
            <thead style="background: #eee">
              <tr>
                <td style="width: 50%">
                  {{ $t("payments.paid_amount") }}
                </td>
                <td style="width: 50%">
                  {{ $t("payments.due_amount") }}
                </td>
              </tr>
            </thead>
            <tbody>
              <tr class="paid-amount-row">
                <td>{{ formatAmountCurrency(order.paid_amount) }}</td>
                <td>{{ formatAmountCurrency(order.due_amount) }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- PAYMENT MODE LINE -->
        <div>
          <table style="width: 100%">
            <tbody>
              <tr style="text-align: center">
                <td style="width: 100%">
                  <h4 style="margin-bottom: 0px" v-if="order.order_payments">
                    {{ $t("invoice.payment_mode") }}:
                    <span
                      v-for="p in order.order_payments"
                      :key="p.xid"
                      style="margin-right: 5px"
                    >
                      {{ formatAmountCurrency(p.amount) }}
                      (<span v-if="p.payment?.payment_mode?.name">
                        {{ p.payment.payment_mode.name }}
                      </span>
                      )
                    </span>
                  </h4>
                  <h3 style="margin-bottom: 0px" v-else>
                    {{ $t("invoice.payment_mode") }}: -
                  </h3>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- DISCOUNT / TAX SUMMARY (OPTIONAL EXTRA SECTION) -->
        <div
          v-if="selectedWarehouse.show_discount_tax_on_invoice"
          class="discount-details"
        >
          <p>
            {{ $t("invoice.total_discount_on_mrp") }} :
            {{ formatAmountCurrency(order.saving_on_mrp) }}
          </p>
          <p>
            {{ $t("invoice.total_discount") }} :
            {{ order.saving_percentage }}%
          </p>
          <p>
            {{ $t("invoice.total_tax") }} :
            {{ formatAmountCurrency(order.total_tax_on_items) }}
          </p>
        </div>

        <!-- BANK DETAIL + TERMS + SIGN (LIKE PDF BOTTOM) -->
        <div class="bottom-row">
          <!-- BANK DETAILS TABLE (with fallback) -->
          <div class="bank-details">
            <h4>BANK DETAILS :</h4>
            <table class="bank-table">
              <tbody>
                <tr>
                  <td class="bank-label">Name</td>
                  <td class="bank-sep">:</td>
                  <td class="bank-value">{{ bankDetails.name }}</td>
                </tr>
                <tr>
                  <td class="bank-label">A/c No</td>
                  <td class="bank-sep">:</td>
                  <td class="bank-value">{{ bankDetails.account_no }}</td>
                </tr>
                <tr>
                  <td class="bank-label">Bank</td>
                  <td class="bank-sep">:</td>
                  <td class="bank-value">{{ bankDetails.bank_name }}</td>
                </tr>
                <tr>
                  <td class="bank-label">Branch</td>
                  <td class="bank-sep">:</td>
                  <td class="bank-value">{{ bankDetails.branch }}</td>
                </tr>
                <tr>
                  <td class="bank-label">IFSC</td>
                  <td class="bank-sep">:</td>
                  <td class="bank-value">{{ bankDetails.ifsc }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- TERMS + AUTHORISED SIGNATORY BOX FOR STAMP -->
          <div class="terms-sign">
            <div class="terms">
              <p class="terms-title">Terms Of Sales :</p>
              <p class="terms-text">
                {{
                  selectedWarehouse.invoice_terms ||
                  "Goods once sold will be taken back or exchanged within 10 Days."
                }}
              </p>
            </div>
            <div class="sign-block">
              <p>For : {{ selectedWarehouse.name }}</p>
              <div class="sign-stamp-box">
                <span>Authorised Signatory</span>
              </div>
            </div>
          </div>
        </div>

        <!-- BARCODE / QR + THANKS -->
        <div
          v-if="selectedWarehouse.barcode_type == 'barcode'"
          class="barcode-details"
        >
          <BarcodeGenerator
            :value="order.invoice_number + ''"
            :height="25"
            :width="1"
            :fontSize="15"
            :elementTag="'svg'"
          />
        </div>
        <div v-else style="text-align: center" class="qrcode-details">
          <QRcodeGenerator :text="order.invoice_number + ''" />
        </div>

        <div class="thanks-details">
          <h3>{{ $t("invoice.thanks_message") }}</h3>
        </div>
      </div>
    </div>

    <template #footer>
      <div class="footer-button">
        <a-button
          v-if="order?.user?.email && isVerified"
          type="primary"
          @click="sendInvoiceMail(order.xid, selectedLang)"
          :loading="isSending"
        >
          <template #icon><SendOutlined /></template>
          {{ $t("common.send_invoice") }}
        </a-button>

        <a-button type="primary" @click="printInvoice">
          <template #icon><PrinterOutlined /></template>
          {{ $t("common.print_invoice") }}
        </a-button>
      </div>
    </template>
  </a-modal>
</template>

<script>
import { ref, defineComponent, onMounted, computed, watch } from "vue";
import { PrinterOutlined, SendOutlined } from "@ant-design/icons-vue";
import common from "../../../../common/composable/common";
import BarcodeGenerator from "../../../../common/components/barcode/BarcodeGenerator.vue";
import QRcodeGenerator from "../../../../common/components/barcode/QRcodeGenerator.vue";
import { notification } from "ant-design-vue";
import { useI18n } from "vue-i18n";

// axiosAdmin is assumed to be globally available as in your existing code
const posInvoiceCssUrl = window.config.pos_invoice_css;

// Default static fallbacks if warehouse does not have GST / Bank details
const DEFAULT_GSTIN = "24BNGPG0699R1ZD"; // 🔁 Replace with your real default GSTIN
const DEFAULT_BANK = {
  name: "ROOPKALA FASHION",
  account_no: "18650200016691",
  bank_name: "THE FEDERAL BANK",
  branch: "SURAT VARACHHA",
  ifsc: "FDRL0001865",
};

export default defineComponent({
  props: {
    visible: { type: Boolean, default: false },
    order: { type: Object, default: null },
    size: { type: String, default: "A4" }, // 'A4' | 'A5' | '80mm' | '58mm'
    autoOpen: { type: Boolean, default: false },
  },
  emits: ["closed", "success"],
  components: {
    PrinterOutlined,
    BarcodeGenerator,
    QRcodeGenerator,
    SendOutlined,
  },
  setup(props, { emit }) {
    const { t } = useI18n();
    const {
      formatAmountCurrency,
      formatDate,
      selectedWarehouse,
      selectedLang,
    } = common();

    const isSending = ref(false);
    const isVerified = ref("");

    // Custom fields cache: { [product_xid]: [{ field_name, field_value }, ...] }
    const productCustomFields = ref({});

    onMounted(() => {
      axiosAdmin.get("verified-email").then((response) => {
        isVerified.value = response.data?.verified;
      });
    });

    // ✅ Console logs + load custom fields whenever order changes
    const fetchProductCustomFields = async (o) => {
      try {
        if (!o || !Array.isArray(o.items)) return;

        const productXids = [
          ...new Set(
            o.items
              .map((item) => item.product?.xid)
              .filter((xid) => !!xid)
          ),
        ];

        if (!productXids.length) return;

        // 🔁 Adjust this API to match your backend implementation
        const res = await axiosAdmin.get("product-custom-fields", {
          params: {
            product_ids: productXids.join(","), // e.g. "jrYAzpr5,darj1yW9"
            warehouse_id: selectedWarehouse.xid, // hashed warehouse id
          },
        });

        // Expecting: { [product_xid]: [{ field_name, field_value }, ...] }
        productCustomFields.value = res.data || {};
        console.log(
          "Loaded product custom fields:",
          JSON.parse(JSON.stringify(productCustomFields.value))
        );
      } catch (e) {
        console.error("Failed to load product custom fields", e);
      }
    };

    watch(
      () => props.order,
      (o) => {
        if (o) {
          console.log("POS Invoice Order:", JSON.parse(JSON.stringify(o)));
          if (Array.isArray(o.items)) {
            console.log(
              "POS Invoice Order Items:",
              JSON.parse(JSON.stringify(o.items))
            );
          }
          fetchProductCustomFields(o);
        }
      },
      { immediate: true }
    );

    const onClose = () => emit("closed");

    const sendInvoiceMail = async (xid, lang) => {
      isSending.value = true;
      try {
        await axiosAdmin.get(`send-mail/${xid}/${lang}`);
        notification.success({
          message: t("common.success"),
          description: t("common.mail_sent_successfully"),
          duration: 4.5,
        });
      } catch (error) {
        notification.error({
          message: t("common.error"),
          description: t("common.failed_to_send_mail"),
          duration: 4.5,
        });
      } finally {
        isSending.value = false;
      }
    };

    const sizeClass = computed(() => {
      const s = (props.size || "A4").toUpperCase();
      if (s === "A5") return "a5-invoice";
      if (s === "80MM") return "thermal-80";
      if (s === "58MM") return "thermal-58";
      return "a4-invoice";
    });

    const warehouseGstNumber = computed(() => {
      return selectedWarehouse.gst_number || DEFAULT_GSTIN;
    });

    const bankDetails = computed(() => {
      const w = selectedWarehouse;
      return {
        name: w.bank_account_name || DEFAULT_BANK.name,
        account_no: w.bank_account_no || DEFAULT_BANK.account_no,
        bank_name: w.bank_name || DEFAULT_BANK.bank_name,
        branch: w.bank_branch || DEFAULT_BANK.branch,
        ifsc: w.ifsc_code || DEFAULT_BANK.ifsc,
      };
    });

    const buildPrintCss = () => {
      const s = (props.size || "A4").toUpperCase();
      const base = `
        .invoice-header { text-align:center; border-bottom:1px dotted #ddd !important; padding-bottom:4px; }
        .invoice-logo { width:100px; margin-bottom:4px; }
        .store-name { margin:0; font-size:18px; font-weight:700; text-transform:uppercase; }
        .store-address { margin:0; white-space:break-spaces; }
        .store-contact { margin:0; font-size:12px; }
        .invoice-meta-row { margin-top:6px; border-bottom:1px dotted #ddd !important; padding-bottom:4px; display:flex; justify-content:space-between; }
        .tax-invoice-title { margin:0; font-size:16px; font-weight:600; text-transform:uppercase; }
        .items-table { width:100%; border-collapse:collapse; margin-top:8px; }
        .items-table th, .items-table td { border:1px solid #ddd; padding:4px; font-size:12px; }
        .items-table thead { background:#eee; font-weight:600; }
        .right { text-align:right; }
        .center { text-align:center; }
        .tax-invoice-totals { margin-top:6px; border-top:2px dotted #ddd !important; border-bottom:2px dotted #ddd !important; padding:4px 0; }
        .paid-amount-deatils { margin-top:10px; text-align:center; }
        .paid-amount-row { border-top:2px dotted #ddd !important; border-bottom:2px dotted #ddd !important; }
        .thanks-details { margin-top:5px; text-align:center; }
        .barcode-details { margin-top:10px; text-align:center; }
        .discount-details { padding:5px 0px; border-top:2px dotted #ddd !important; border-bottom:2px dotted #ddd !important; }
        .discount-details p { margin-bottom:0px; }
        .bottom-row { display:flex; justify-content:space-between; margin-top:8px; font-size:12px; }
        .bank-details { width:45%; }
        .bank-table { width:100%; border-collapse:collapse; font-size:12px; }
        .bank-table td { padding:2px 2px; }
        .bank-label { width:30%; font-weight:500; }
        .bank-sep { width:5%; }
        .bank-value { width:65%; }
        .terms-sign { width:50%; text-align:right; }
        .terms-title { margin:0 0 2px 0; font-weight:600; }
        .terms-text { margin:0 0 6px 0; }
        .sign-block { margin-top:8px; display:inline-block; }
        .sign-stamp-box { border:1px solid #000; padding:12px 8px 32px; min-height:60px; margin-top:4px; display:flex; align-items:flex-end; justify-content:center; font-size:12px; }
        table { width:100%; border-collapse:collapse; }
        thead { background:#eee; }
        @media print {
          table { page-break-inside:auto; }
          tr, td, th { page-break-inside:avoid; }
        }
      `;

      if (s === "A5") {
        return `
          @page { size: A5; margin: 10mm; }
          .invoice-root { max-width: 148mm; width: 148mm; }
          body, table { font-size: 12px; }
          ${base}
        `;
      }
      if (s === "80MM") {
        return `
          @page { size: 80mm auto; margin: 5mm; }
          .invoice-root { max-width: 80mm; width: 80mm; }
          body, table { font-size: 11px; }
          .invoice-logo { width: 70px; }
          ${base}
        `;
      }
      if (s === "58MM") {
        return `
          @page { size: 58mm auto; margin: 4mm; }
          .invoice-root { max-width: 58mm; width: 58mm; }
          body, table { font-size: 10px; }
          .invoice-logo { width: 60px; }
          ${base}
        `;
      }
      return `
        @page { size: A4; margin: 12mm; }
        .invoice-root { max-width: 210mm; width: 210mm; }
        body, table { font-size: 13px; }
        ${base}
      `;
    };

    const printInvoice = () => {
      const wrapper = document.getElementById("pos-invoice-inner");
      if (!wrapper) return;
      const invoiceContent = wrapper.outerHTML;
      const newWindow = window.open("", "", "height=800,width=800");
      newWindow.document.write(`
        <html>
          <head>
            <meta charset="utf-8" />
            <link rel="stylesheet" href="${posInvoiceCssUrl}">
            <style>
              ${buildPrintCss()}
            </style>
          </head>
          <body>${invoiceContent}</body>
        </html>
      `);
      newWindow.document.close();
      newWindow.focus();
      newWindow.print();
    };

    // Helpers for item-level taxable and tax rate
    const getTaxableAmount = (item) => {
      const subtotal = Number(item.subtotal) || 0;
      const tax =
        Number(item.tax_amount) ||
        Number(item.total_tax) ||
        0;
      const taxable = subtotal - tax;
      return taxable > 0 ? taxable : subtotal;
    };

    const getTaxRate = (item) => {
      if (
        item.tax_rate !== null &&
        item.tax_rate !== undefined &&
        item.tax_rate !== ""
      ) {
        return Number(item.tax_rate);
      }
      const subtotal = Number(item.subtotal) || 0;
      const tax =
        Number(item.tax_amount) ||
        Number(item.total_tax) ||
        0;
      const taxable = subtotal - tax;
      if (taxable > 0 && tax > 0) {
        return Number(((tax / taxable) * 100).toFixed(2));
      }
      return 0;
    };

    const getGrossAmount = (order) => {
      if (order.subtotal) return order.subtotal;
      const total = Number(order.total) || 0;
      const tax = Number(order.tax_amount) || 0;
      const discount = Number(order.discount) || 0;
      const shipping = Number(order.shipping) || 0;
      return total - tax + discount - shipping;
    };

    // GST slab summary from items (group by tax_rate)
    const gstSummary = computed(() => {
      const map = {};
      if (!props.order || !Array.isArray(props.order.items)) return [];

      props.order.items.forEach((item) => {
        const rate = getTaxRate(item) || 0;
        const taxable = getTaxableAmount(item) || 0;
        const tax =
          Number(item.tax_amount) ||
          Number(item.total_tax) ||
          taxable * (rate / 100);

        if (!map[rate]) {
          map[rate] = { rate, taxable: 0, taxAmount: 0, net: 0 };
        }
        map[rate].taxable += taxable;
        map[rate].taxAmount += tax;
        map[rate].net += taxable + tax;
      });

      return Object.values(map).sort((a, b) => a.rate - b.rate);
    });

    const gstSummaryTotals = computed(() => {
      return gstSummary.value.reduce(
        (acc, row) => {
          acc.taxable += row.taxable;
          acc.taxAmount += row.taxAmount;
          acc.net += row.net;
          return acc;
        },
        { taxable: 0, taxAmount: 0, net: 0 }
      );
    });

    // Helpers for custom fields / HSN from product_custom_fields
    const getItemCustomFields = (item) => {
      const xid = item.product?.xid;
      if (!xid) return [];
      return productCustomFields.value[xid] || [];
    };

    const getItemHsnCode = (item) => {
      // Priority: direct item field -> product field -> custom field "HSN Code"
      const direct = item.hsn_code || item.product?.hsn_code;
      if (direct) return direct;

      const fields = getItemCustomFields(item);
      const hsnField = fields.find((f) => {
        const name = (f.field_name || "").toLowerCase();
        return name === "hsn code" || name === "hsn";
      });

      return hsnField?.field_value || "-";
    };

    watch(
      () => props.visible,
      (v) => {
        if (v && props.autoOpen) {
          setTimeout(() => printInvoice(), 250);
        }
      }
    );

    return {
      onClose,
      formatDate,
      selectedWarehouse,
      formatAmountCurrency,
      printInvoice,
      selectedLang,
      sendInvoiceMail,
      isSending,
      isVerified,
      sizeClass,
      getTaxableAmount,
      getTaxRate,
      getGrossAmount,
      gstSummary,
      gstSummaryTotals,
      warehouseGstNumber,
      bankDetails,
      getItemCustomFields,
      getItemHsnCode,
    };
  },
});
</script>

<style>
.invoice-header {
  text-align: center;
  border-bottom: 1px dotted #ddd !important;
  padding-bottom: 6px;
}
.invoice-logo {
  width: 100px;
  margin-bottom: 4px;
}
.invoice-header-text {
  margin-top: 4px;
}
.store-name {
  margin: 0;
  font-size: 18px;
  font-weight: 700;
  text-transform: uppercase;
}
.store-address {
  margin: 0;
  white-space: break-spaces;
}
.store-contact {
  margin: 0;
  font-size: 12px;
}
.store-gst {
  font-weight: 500;
}

.invoice-meta-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-top: 6px;
  border-bottom: 1px dotted #ddd !important;
  padding-bottom: 4px;
}
.tax-invoice-title {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
  text-transform: uppercase;
}
.invoice-meta-table {
  width: auto;
  font-size: 12px;
}
.invoice-meta-table td {
  padding: 1px 4px;
}
.meta-label {
  font-weight: 500;
  white-space: nowrap;
}
.meta-value {
  white-space: nowrap;
}

/* Bill to party row */
.bill-party-row {
  display: flex;
  justify-content: space-between;
  margin-top: 6px;
  padding-bottom: 4px;
}
.bill-title {
  margin: 0 0 2px 0;
  font-size: 14px;
  font-weight: 600;
}
.party-line {
  margin: 0;
  font-size: 12px;
}
.party-name {
  font-weight: 600;
}

/* Items table */
.tax-invoice-items {
  margin-top: 8px;
}
.items-table {
  width: 100%;
  border-collapse: collapse;
}
.items-table th,
.items-table td {
  border: 1px solid #ddd;
  padding: 4px;
  font-size: 12px;
}
.items-table thead {
  background: #eee;
}
.item-name {
  font-weight: 500;
}
.item-custom-fields {
  margin-top: 2px;
}
.item-custom-field-line {
  display: block;
  font-size: 11px;
  line-height: 1.2;
}

.center {
  text-align: center;
}
.right {
  text-align: right;
}

/* Totals and GST summary */
.tax-invoice-totals {
  margin-top: 6px;
  border-top: 2px dotted #ddd !important;
  border-bottom: 2px dotted #ddd !important;
  padding: 4px 0;
}
.amount-summary-block p {
  margin: 0;
  font-size: 12px;
}
.amount-words {
  margin-top: 4px;
  font-style: italic;
}
.gst-summary-block table {
  width: 100%;
  border-collapse: collapse;
  font-size: 11px;
}
.gst-summary-block th,
.gst-summary-block td {
  border: 1px solid #ddd;
  padding: 3px;
}
.gst-summary-block thead {
  background: #f5f5f5;
}
.gst-summary-total {
  font-weight: 600;
}

/* Paid / Due, footer etc */
.paid-amount-deatils {
  margin-top: 10px;
  text-align: center;
}
.paid-amount-row {
  border-top: 2px dotted #ddd !important;
  border-bottom: 2px dotted #ddd !important;
}
.thanks-details {
  margin-top: 5px;
  text-align: center;
}
.barcode-details {
  margin-top: 10px;
  text-align: center;
}
.footer-button {
  text-align: center !important;
}
.discount-details {
  padding: 5px 0px;
  border-top: 2px dotted #ddd !important;
  border-bottom: 2px dotted #ddd !important;
}
.discount-details p {
  margin-bottom: 0px;
}

/* Bank + terms bottom row */
.bottom-row {
  display: flex;
  justify-content: space-between;
  margin-top: 8px;
  font-size: 12px;
}

/* Bank table */
.bank-details {
  width: 45%;
}
.bank-details h4 {
  margin: 0 0 4px 0;
}
.bank-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 12px;
}
.bank-table td {
  padding: 2px 2px;
}
.bank-label {
  width: 30%;
  font-weight: 500;
}
.bank-sep {
  width: 5%;
}
.bank-value {
  width: 65%;
}

/* Terms + sign block */
.terms-sign {
  width: 50%;
  text-align: right;
}
.terms-title {
  margin: 0 0 2px 0;
  font-weight: 600;
}
.terms-text {
  margin: 0 0 6px 0;
}
.sign-block {
  margin-top: 8px;
  display: inline-block;
}
.sign-stamp-box {
  border: 1px solid #000;
  padding: 12px 8px 32px;
  min-height: 60px;
  margin-top: 4px;
  display: flex;
  align-items: flex-end;
  justify-content: center;
  font-size: 12px;
}

/* size previews */
.invoice-root {
  width: 100%;
}
.a4-invoice {
  max-width: 210mm;
}
.a5-invoice {
  max-width: 148mm;
}
.thermal-80 {
  max-width: 80mm;
}
.thermal-58 {
  max-width: 58mm;
}
.thermal-80,
.thermal-58 {
  font-size: 12px;
}
</style>