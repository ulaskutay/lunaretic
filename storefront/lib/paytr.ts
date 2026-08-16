export type PaytrPrepareResponse = {
  post_url: string;
  fields: Record<string, string | number>;
  order_id: number;
  dev?: boolean;
  success_url?: string;
  callback_url?: string;
  merchant_oid?: string;
  total_amount?: string;
};

export type PaytrCardInput = {
  cc_owner: string;
  card_number: string;
  card_expiry: string;
  cvv: string;
};

function parseExpiry(value: string): { month: string; year: string } | null {
  const match = value.replace(/\s+/g, "").match(/^(\d{2})\/?(\d{2,4})$/);
  if (!match) return null;

  const month = match[1];
  let year = match[2];
  if (year.length === 4) {
    year = year.slice(-2);
  }

  return { month, year };
}

async function completeDevPayment(payload: PaytrPrepareResponse): Promise<void> {
  const body = new URLSearchParams({
    merchant_oid: payload.merchant_oid ?? "",
    status: "success",
    total_amount: payload.total_amount ?? "",
  });

  await fetch(payload.callback_url ?? "", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body,
  });

  if (payload.success_url) {
    window.location.href = payload.success_url;
  }
}

export async function submitPaytrDirectPayment(
  payload: PaytrPrepareResponse,
  card: PaytrCardInput,
): Promise<void> {
  const owner = card.cc_owner.trim();
  const cardNumber = card.card_number.replace(/\s+/g, "");
  const expiry = parseExpiry(card.card_expiry);
  const cvv = card.cvv.trim();

  if (!owner || cardNumber.length < 12 || !expiry || cvv.length < 3) {
    throw new Error("Kart bilgilerini kontrol edin.");
  }

  if (payload.dev) {
    await completeDevPayment(payload);
    return;
  }

  const paytrForm = document.createElement("form");
  paytrForm.method = "POST";
  paytrForm.action = payload.post_url;
  paytrForm.style.display = "none";

  const appendHidden = (name: string, value: string) => {
    const input = document.createElement("input");
    input.type = "hidden";
    input.name = name;
    input.value = value;
    paytrForm.appendChild(input);
  };

  Object.entries(payload.fields).forEach(([name, value]) => {
    appendHidden(name, String(value));
  });

  appendHidden("cc_owner", owner);
  appendHidden("card_number", cardNumber);
  appendHidden("expiry_month", expiry.month);
  appendHidden("expiry_year", expiry.year);
  appendHidden("cvv", cvv);

  document.body.appendChild(paytrForm);
  paytrForm.submit();
}
