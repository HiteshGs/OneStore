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
        <div class="invoice-header">
          <img
            class="invoice-logo"
            :src="selectedWarehouse.logo_url"
            :alt="selectedWarehouse.name"
          />
        </div>

        <div class="company-details">
          <h2>{{ selectedWarehouse.name }}</h2>
          <p class="company-address">{{ selectedWarehouse.address }}</p>
          <h4 style="margin-bottom: 0px">
            {{ $t("common.phone") }}: {{ selectedWarehouse.phone }}
          </h4>
          <h4>{{ $t("common.email") }}: {{ selectedWarehouse.email }}</h4>
        </div>

        <div class="tax-invoice-details">
          <h3 class="tax-invoice-title">{{ $t("sales.tax_invoice") }}</h3>
          <table class="invoice-customer-details">
            <tbody>
              <tr>
                <td style="width: 50%">
                  {{ $t("sales.invoice") }} &nbsp;&nbsp;&nbsp;&nbsp;:
                  {{ order.invoice_number }}
                </td>
                <td style="width: 50%">
                  {{ $t("common.date") }} : {{ formatDate(order.order_date) }}
                </td>
              </tr>
              <tr>
                <td style="width: 50%">
                  {{ $t("stock.customer") }} : {{ order.user.name }}
                </td>
                <td style="width: 50%">
                  {{ $t("stock.sold_by") }} : {{ order.staff_member.name }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="tax-invoice-items">
          <table style="width: 100%">
            <thead style="background: #eee">
              <tr>
                <td style="width: 5%">#</td>
                <td style="width: 25%">{{ $t("common.item") }}</td>
                <td
                  :style="{
                    width: selectedWarehouse.show_mrp_on_invoice ? '10%' : '20%',
                  }"
                >
                  {{ $t("common.qty") }}
                </td>

                <td
                  v-if="selectedWarehouse.show_mrp_on_invoice"
                  style="width: 15%"
                >
                  {{ $t("product.mrp") }}
                </td>

                <!-- Tax column -->
                <td style="width: 15%">
                  {{ $t("product.tax") }}
                </td>

                <td
                  :style="{
                    width: selectedWarehouse.show_mrp_on_invoice ? '15%' : '20%',
                  }"
                >
                  {{ $t("common.rate") }}
                </td>
                <td
                  :style="{
                    width: selectedWarehouse.show_mrp_on_invoice
                      ? '20%'
                      : '25%',
                    textAlign: 'right',
                  }"
                >
                  {{ $t("common.total") }}
                </td>
              </tr>
            </thead>
            <tbody>
              <tr
                class="item-row"
                v-for="(item, index) in order.items"
                :key="item.xid"
              >
                <td>{{ index + 1 }}</td>

                <!-- Item name + custom fields -->
                <td>
                  {{ item.product.name }}

                  <!-- Custom fields for each item -->
                  <div
                    v-if="item.custom_fields && item.custom_fields.length"
                    class="item-custom-fields"
                  >
                    <small
                      v-for="cf in item.custom_fields"
                      :key="cf.id || cf.name || cf.label"
                      class="item-custom-field-line"
                    >
                      {{ cf.label || cf.name }}: {{ cf.value }}
                    </small>
                  </div>
                </td>

                <td>
                  {{ item.quantity + "" + (item.unit?.short_name || "") }}
                </td>

                <td v-if="selectedWarehouse.show_mrp_on_invoice">
                  {{ item.mrp ? formatAmountCurrency(item.mrp) : "-" }}
                </td>

                <!-- Per-row tax -->
                <td>
                  <div v-if="item.tax_rate || item.tax_amount">
                    <div v-if="item.tax_rate">
                      {{ item.tax_rate }}%
                    </div>
                    <div v-if="item.tax_amount">
                      ({{ formatAmountCurrency(item.tax_amount) }})
                    </div>
                  </div>
                  <div v-else>-</div>
                </td>

                <td>
                  {{ formatAmountCurrency(item.unit_price) }}
                </td>
                <td style="text-align: right">
                  {{ formatAmountCurrency(item.subtotal) }}
                </td>
              </tr>

              <!-- Order tax row -->
              <tr class="item-row-other">
                <td
                  :colspan="
                    selectedWarehouse.show_mrp_on_invoice ? 5 : 4
                  "
                  style="text-align: right"
                >
                  {{ $t("stock.order_tax") }}
                </td>
                <td colspan="2" style="text-align: right">
                  {{ formatAmountCurrency(order.tax_amount) }}
                </td>
              </tr>

              <!-- Discount row -->
              <tr class="item-row-other">
                <td
                  :colspan="
                    selectedWarehouse.show_mrp_on_invoice ? 5 : 4
                  "
                  style="text-align: right"
                >
                  {{ $t("stock.discount") }}
                </td>
                <td colspan="2" style="text-align: right">
                  {{ formatAmountCurrency(order.discount) }}
                </td>
              </tr>

              <!-- Shipping row -->
              <tr class="item-row-other">
                <td
                  :colspan="
                    selectedWarehouse.show_mrp_on_invoice ? 5 : 4
                  "
                  style="text-align: right"
                >
                  {{ $t("stock.shipping") }}
                </td>
                <td colspan="2" style="text-align: right">
                  {{ formatAmountCurrency(order.shipping) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="tax-invoice-totals">
          <table style="width: 100%">
            <tbody>
              <tr>
                <td style="width: 30%">
                  <h3 style="margin-bottom: 0px">
                    {{ $t("common.items") }}: {{ order.total_items }}
                  </h3>
                </td>
                <td style="width: 30%">
                  <h3 style="margin-bottom: 0px">
                    {{ $t("common.qty") }}: {{ order.total_quantity }}
                  </h3>
                </td>
                <td style="width: 40%; text-align: center">
                  <h3 style="margin-bottom: 0px">
                    {{ $t("common.total") }}:
                    {{ formatAmountCurrency(order.total) }}
                  </h3>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

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

const posInvoiceCssUrl = window.config.pos_invoice_css;

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

    onMounted(() => {
      axiosAdmin.get("verified-email").then((response) => {
        isVerified.value = response.data?.verified;
      });
    });

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

    const buildPrintCss = () => {
      const s = (props.size || "A4").toUpperCase();
      if (s === "A5") {
        return `
          @page { size: A5; margin: 10mm; }
          .invoice-root { max-width: 148mm; width: 148mm; }
          body, table { font-size: 12px; }
        `;
      }
      if (s === "80MM") {
        return `
          @page { size: 80mm auto; margin: 5mm; }
          .invoice-root { max-width: 80mm; width: 80mm; }
          body, table { font-size: 11px; }
          .invoice-logo { width: 70px; }
          .company-details h2 { font-size: 14px; }
          .company-details, .tax-invoice-title { margin-top: 2px; }
          table td { padding: 2px 0; }
        `;
      }
      if (s === "58MM") {
        return `
          @page { size: 58mm auto; margin: 4mm; }
          .invoice-root { max-width: 58mm; width: 58mm; }
          body, table { font-size: 10px; }
          .invoice-logo { width: 60px; }
          .company-details h2 { font-size: 12px; }
          .tax-invoice-title { font-size: 12px; }
          table td { padding: 2px 0; }
        `;
      }
      return `
        @page { size: A4; margin: 12mm; }
        .invoice-root { max-width: 210mm; width: 210mm; }
        body, table { font-size: 13px; }
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
              .invoice-header { text-align:center; border-bottom:1px dotted #ddd !important; }
              .invoice-logo { width:100px; }
              .company-details { text-align:center; margin-top:5px; border-bottom:2px dotted #ddd !important; }
              .company-address { white-space:break-spaces; margin-bottom:0px; }
              .tax-invoice-title { text-align:center; margin-top:5px; }
              .tax-invoice-items { margin-top:10px; }
              .item-row { border-bottom:1px dotted #ddd !important; }
              .tax-invoice-totals { margin-top:5px; border-top:2px dotted #ddd !important; border-bottom:2px dotted #ddd !important; }
              .paid-amount-deatils { margin-top:10px; text-align:center; }
              .paid-amount-row { border-top:2px dotted #ddd !important; border-bottom:2px dotted #ddd !important; }
              .thanks-details { margin-top:5px; text-align:center; }
              .barcode-details { margin-top:10px; text-align:center; }
              .discount-details { padding:5px 0px; border-top:2px dotted #ddd !important; border-bottom:2px dotted #ddd !important; }
              .discount-details p { margin-bottom:0px; }
              table { width:100%; border-collapse:collapse; }
              thead { background:#eee; }
              @media print {
                table { page-break-inside:auto; }
                tr, td, th { page-break-inside:avoid; }
              }
            </style>
          </head>
          <body>${invoiceContent}</body>
        </html>
      `);
      newWindow.document.close();
      newWindow.focus();
      newWindow.print();
    };

    // 🔍 Log order + each item whenever modal opens
    watch(
      () => props.visible,
      (v) => {
        if (v) {
          console.log(
            "[InvoiceModal] Order data:",
            JSON.parse(JSON.stringify(props.order))
          );

          if (props.order && Array.isArray(props.order.items)) {
            props.order.items.forEach((item, index) => {
              console.log(
                `[InvoiceModal] Item #${index + 1}:`,
                JSON.parse(JSON.stringify(item))
              );
            });
          }

          if (props.autoOpen) {
            setTimeout(() => printInvoice(), 250);
          }
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
    };
  },
});
</script>

<style>
.invoice-header {
  text-align: center;
  border-bottom: 1px dotted #ddd !important;
}
.invoice-logo {
  width: 100px;
}
.company-details {
  text-align: center;
  margin-top: 5px;
  border-bottom: 2px dotted #ddd !important;
}
.company-address {
  white-space: break-spaces;
  margin-bottom: 0px;
}
.invoice-customer-details {
  width: 100%;
  margin-bottom: 5px;
}
.tax-invoice-title {
  text-align: center;
  margin-top: 5px;
}
.tax-invoice-items {
  margin-top: 10px;
}
.item-row {
  border-bottom: 1px dotted #ddd !important;
}
.tax-invoice-totals {
  margin-top: 5px;
  border-top: 2px dotted #ddd !important;
  border-bottom: 2px dotted #ddd !important;
}
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

/* Item custom fields under product name */
.item-custom-fields {
  margin-top: 2px;
}
.item-custom-field-line {
  display: block;
  font-size: 11px;
  line-height: 1.2;
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