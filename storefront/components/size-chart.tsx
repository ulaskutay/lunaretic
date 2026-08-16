"use client";

import { useEffect, useId, useRef, useState } from "react";
import { createPortal } from "react-dom";

const rows = [
  { size: "XS", chest: "84–88", waist: "68–72", hip: "86–90" },
  { size: "S", chest: "88–92", waist: "72–76", hip: "90–94" },
  { size: "M", chest: "92–96", waist: "76–80", hip: "94–98" },
  { size: "L", chest: "96–100", waist: "80–84", hip: "98–102" },
  { size: "XL", chest: "100–104", waist: "84–88", hip: "102–106" },
  { size: "XXL", chest: "104–110", waist: "88–94", hip: "106–112" },
];

function TapeIcon() {
  return (
    <svg viewBox="0 0 24 24" aria-hidden="true">
      <rect x="2.5" y="8" width="19" height="8" rx="1.2" fill="none" stroke="currentColor" strokeWidth="1.4" />
      <path d="M6 8v3.2M9.5 8v2.2M13 8v3.2M16.5 8v2.2M20 8v3.2" fill="none" stroke="currentColor" strokeWidth="1.3" />
    </svg>
  );
}

export function SizeChart() {
  const dialog = useRef<HTMLDialogElement>(null);
  const titleId = useId();
  const [mounted, setMounted] = useState(false);

  useEffect(() => {
    setMounted(true);
  }, []);

  useEffect(() => {
    const node = dialog.current;

    if (!node) {
      return;
    }

    function onClick(event: MouseEvent) {
      if (event.target === node) {
        node?.close();
      }
    }

    node.addEventListener("click", onClick);

    return () => node.removeEventListener("click", onClick);
  }, [mounted]);

  const panel = (
    <dialog ref={dialog} className="etic-pdp__size-chart" aria-labelledby={titleId}>
      <div className="etic-pdp__size-chart-panel">
        <header>
          <h2 id={titleId}>Beden tablosu</h2>
          <button type="button" onClick={() => dialog.current?.close()} aria-label="Kapat">
            ×
          </button>
        </header>
        <div className="etic-pdp__size-chart-table">
          <table>
            <thead>
              <tr>
                <th>Beden</th>
                <th>Göğüs</th>
                <th>Bel</th>
                <th>Basen</th>
              </tr>
            </thead>
            <tbody>
              {rows.map((row) => (
                <tr key={row.size}>
                  <th scope="row">{row.size}</th>
                  <td>{row.chest}</td>
                  <td>{row.waist}</td>
                  <td>{row.hip}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        <p>Ölçüler santimetre cinsindendir. Vücut ölçünüz aralığın içindeyse o bedeni seçin; iki beden arasında kalırsanız kalıp dar ise büyüğe geçin.</p>
      </div>
    </dialog>
  );

  return (
    <>
      <button
        type="button"
        className="etic-pdp__size-chart-trigger"
        onClick={() => dialog.current?.showModal()}
      >
        <span>— Beden tablosu</span>
        <TapeIcon />
      </button>
      {mounted ? createPortal(panel, document.body) : null}
    </>
  );
}
