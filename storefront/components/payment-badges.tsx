const badges = [
  {
    name: "Visa",
    svg: (
      <svg viewBox="0 0 38 24" aria-hidden="true">
        <rect width="38" height="24" rx="3.5" fill="#1A1F71" />
        <text
          x="19"
          y="16"
          textAnchor="middle"
          fill="#fff"
          fontFamily="Arial, Helvetica, sans-serif"
          fontSize="11"
          fontStyle="italic"
          fontWeight="800"
          letterSpacing="0.5"
        >
          VISA
        </text>
      </svg>
    ),
  },
  {
    name: "Mastercard",
    svg: (
      <svg viewBox="0 0 38 24" aria-hidden="true">
        <rect width="38" height="24" rx="3.5" fill="#fff" />
        <circle cx="15.2" cy="12" r="7.2" fill="#EB001B" />
        <circle cx="22.8" cy="12" r="7.2" fill="#F79E1B" />
        <path
          d="M19 6.7a7.2 7.2 0 0 1 0 10.6 7.2 7.2 0 0 1 0-10.6Z"
          fill="#FF5F00"
        />
      </svg>
    ),
  },
  {
    name: "Troy",
    svg: (
      <svg viewBox="0 0 38 24" aria-hidden="true">
        <rect width="38" height="24" rx="3.5" fill="#fff" />
        <circle cx="11.5" cy="12" r="6.2" fill="#E31E24" />
        <text
          x="12"
          y="15.2"
          textAnchor="middle"
          fill="#fff"
          fontFamily="Arial, Helvetica, sans-serif"
          fontSize="9"
          fontWeight="800"
        >
          T
        </text>
        <text
          x="26.2"
          y="15.4"
          textAnchor="middle"
          fill="#1A237E"
          fontFamily="Arial, Helvetica, sans-serif"
          fontSize="8"
          fontWeight="800"
          letterSpacing="0.4"
        >
          TROY
        </text>
      </svg>
    ),
  },
  {
    name: "American Express",
    svg: (
      <svg viewBox="0 0 38 24" aria-hidden="true">
        <rect width="38" height="24" rx="3.5" fill="#006FCF" />
        <text
          x="19"
          y="15.5"
          textAnchor="middle"
          fill="#fff"
          fontFamily="Arial, Helvetica, sans-serif"
          fontSize="7.5"
          fontWeight="800"
          letterSpacing="0.3"
        >
          AMEX
        </text>
      </svg>
    ),
  },
];

export function PaymentBadges() {
  return (
    <ul className="etic-footer__payments" aria-label="Kabul edilen ödeme yöntemleri">
      {badges.map((badge) => (
        <li key={badge.name}>
          <span className="etic-footer__payment" title={badge.name}>
            {badge.svg}
            <span className="sr-only">{badge.name}</span>
          </span>
        </li>
      ))}
    </ul>
  );
}
