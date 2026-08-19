export default function Loading() {
  return (
    <div className="etic-route-loading" aria-busy="true" aria-live="polite">
      <div className="etic-route-loading__hero" />
      <div className="etic-route-loading__grid">
        <span />
        <span />
        <span />
        <span />
      </div>
    </div>
  );
}
