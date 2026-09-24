<?php
declare(strict_types=1);
header('Content-Type: text/css; charset=UTF-8');
?>
:root {
  --bg: #0f1115;
  --card-bg: #171a21;
  --border: #262b36;
  --text: #e5e7eb;
  --muted: #9ca3af;
  --accent: #6366f1;
  --success: #22c55e;
  --error: #ef4444;
  --input-bg: #0f1115;
  --connection-bg: #12151b;
  --connection-hover: #1b1f28;
  --icon-bg: #202531;
  --modal-backdrop: rgba(0,0,0,.72);
  --shadow: rgba(0,0,0,.45);
}

html[data-theme="light"] {
  --bg: #f4f6f8;
  --card-bg: #ffffff;
  --border: #dfe3e8;
  --text: #171a21;
  --muted: #6b7280;
  --accent: #4f46e5;
  --success: #16a34a;
  --error: #dc2626;
  --input-bg: #ffffff;
  --connection-bg: #f8fafc;
  --connection-hover: #eef2f7;
  --icon-bg: #e9edf3;
  --modal-backdrop: rgba(15,17,21,.55);
  --shadow: rgba(15,17,21,.18);
}

* { box-sizing: border-box; }

html { color-scheme: dark; }
html[data-theme="light"] { color-scheme: light; }

body {
  margin: 0;
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
  background: var(--bg);
  color: var(--text);
  transition: background-color .2s ease, color .2s ease;
}

body.modal-open { overflow: hidden; }
a { color: var(--accent); text-decoration: none; }

.brand-title { display: flex; align-items: center; gap: 9px; }
.brand-icon { width: 28px; height: 28px; display: block; flex: 0 0 auto; border-radius: 7px; }
.install-card .brand-icon { width: 34px; height: 34px; border-radius: 9px; }

.topbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  padding: 16px 24px;
  border-bottom: 1px solid var(--border);
}

.topbar h1 { font-size: 20px; margin: 0; }
.topbar-actions { display: flex; align-items: center; gap: 8px; }

.theme-toggle {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  border: 1px solid var(--border);
  background: var(--card-bg);
  color: var(--text);
  padding: 8px 11px;
  border-radius: 8px;
  cursor: pointer;
  font-size: 13px;
  transition: background .15s, border-color .15s;
}
.theme-toggle:hover { border-color: var(--accent); }
.theme-toggle-icon { font-size: 15px; line-height: 1; }
.theme-toggle-fixed { position: fixed; top: 18px; right: 18px; z-index: 1000; }

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 24px;
}

.card, .stat-card {
  background: var(--card-bg);
  border: 1px solid var(--border);
}

.card {
  border-radius: 12px;
  padding: 20px;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}

.stat-card {
  border-radius: 12px;
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.stat-value { font-size: 28px; font-weight: 700; }
.stat-label {
  color: var(--muted);
  font-size: 13px;
  text-transform: uppercase;
  letter-spacing: .04em;
}

.charts-grid {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 16px;
  margin-bottom: 24px;
}

@media (max-width: 800px) { .charts-grid { grid-template-columns: 1fr; } }
.chart-card h2 { margin-top: 0; }

.chart-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
}

.chart-tabs button {
  background: transparent;
  border: 1px solid var(--border);
  color: var(--muted);
  padding: 6px 12px;
  border-radius: 8px;
  cursor: pointer;
  margin-left: 4px;
  font-size: 13px;
}

.chart-tabs button.active {
  background: var(--accent);
  color: white;
  border-color: var(--accent);
}

.tables-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}
.tables-grid h2 { margin-top: 0; font-size: 16px; }

table { width: 100%; border-collapse: collapse; font-size: 14px; }
th, td { text-align: left; padding: 8px; border-bottom: 1px solid var(--border); }
th { color: var(--muted); font-weight: 600; }
.table-scroll { overflow-x: auto; }
.muted { color: var(--muted); }

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 14px;
}
.section-header h2 { margin: 0; }
.section-header p { margin: 5px 0 0; color: var(--muted); font-size: 13px; }

.connection-list { display: flex; flex-direction: column; gap: 8px; }

.connection-item {
  width: 100%;
  display: grid;
  grid-template-columns: minmax(210px, 1fr) minmax(160px, .8fr) 160px 24px;
  gap: 16px;
  align-items: center;
  padding: 13px 14px;
  border: 1px solid var(--border);
  border-radius: 10px;
  background: var(--connection-bg);
  color: var(--text);
  text-align: left;
  cursor: pointer;
  transition: border-color .15s, background .15s, transform .15s;
}
.connection-item:hover {
  background: var(--connection-hover);
  border-color: var(--accent);
  transform: translateY(-1px);
}

.connection-device, .connection-info { display: flex; align-items: center; min-width: 0; }
.connection-device { gap: 12px; }
.device-icon, .details-icon {
  display: grid;
  place-items: center;
  border-radius: 9px;
  background: var(--icon-bg);
}
.device-icon { width: 38px; height: 38px; flex: 0 0 38px; font-size: 20px; }
.device-main, .connection-info { display: flex; flex-direction: column; gap: 3px; min-width: 0; }
.device-main strong, .connection-info strong { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.device-main small, .connection-info small, .connection-date {
  color: var(--muted);
  font-size: 12px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.connection-date { text-align: right; }
.connection-arrow { color: var(--muted); font-size: 25px; line-height: 1; }

.empty-state {
  padding: 30px;
  text-align: center;
  color: var(--muted);
  border: 1px dashed var(--border);
  border-radius: 10px;
}

.details-modal {
  position: fixed;
  inset: 0;
  display: none;
  align-items: center;
  justify-content: center;
  padding: 20px;
  z-index: 9999;
}
.details-modal.open { display: flex; }
.details-backdrop { position: absolute; inset: 0; background: var(--modal-backdrop); backdrop-filter: blur(3px); }
.details-dialog {
  position: relative;
  z-index: 1;
  width: min(680px, 100%);
  max-height: min(760px, 90vh);
  overflow: auto;
  background: var(--card-bg);
  border: 1px solid var(--border);
  border-radius: 14px;
  box-shadow: 0 20px 60px var(--shadow);
}
.details-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 16px;
  padding: 18px 20px;
  border-bottom: 1px solid var(--border);
}
.details-header > div:first-child { display: flex; align-items: center; gap: 12px; min-width: 0; }
.details-header h2 { margin: 0; font-size: 18px; }
#details-subtitle { display: block; margin-top: 4px; color: var(--muted); font-size: 12px; }
.details-icon { width: 42px; height: 42px; font-size: 22px; }
.details-close { border: 0; background: transparent; color: var(--muted); font-size: 28px; line-height: 1; cursor: pointer; padding: 0 4px; }
.details-close:hover { color: var(--text); }
.details-content { padding: 8px 20px 20px; }
.detail-row { display: grid; grid-template-columns: 150px minmax(0,1fr); gap: 15px; padding: 11px 0; border-bottom: 1px solid var(--border); }
.detail-label { color: var(--muted); font-size: 13px; }
.detail-value { min-width: 0; overflow-wrap: anywhere; font-size: 13px; }

.install-page { display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 24px; }
.install-card { width: 100%; max-width: 480px; }
.install-card h1 { margin-top: 0; }
form label { display: block; margin-bottom: 12px; font-size: 14px; }
form input[type="text"], form input[type="password"] {
  width: 100%; padding: 10px; margin-top: 4px; border-radius: 8px;
  border: 1px solid var(--border); background: var(--input-bg); color: var(--text);
}
.checkbox { display: flex; align-items: center; gap: 8px; }
.checkbox input { width: auto; }
fieldset { border: 1px solid var(--border); border-radius: 8px; margin-bottom: 16px; }
legend { padding: 0 6px; color: var(--muted); font-size: 13px; }
.btn { display: inline-block; background: var(--accent); color: white; border: none; padding: 10px 18px; border-radius: 8px; cursor: pointer; font-size: 14px; }
.alert { padding: 10px 14px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; }
.alert-error { background: rgba(239,68,68,.15); color: #fca5a5; border: 1px solid rgba(239,68,68,.4); }
.alert-success { background: rgba(34,197,94,.15); color: #86efac; border: 1px solid rgba(34,197,94,.4); }

@media (max-width: 800px) {
  .connection-item { grid-template-columns: 1fr 24px; }
  .connection-info, .connection-date { display: none; }
  .detail-row { grid-template-columns: 1fr; gap: 4px; }
  .topbar { padding: 14px 16px; }
  .topbar-actions a { display: none; }
  .theme-toggle span:last-child { display: none; }
  .container { padding: 16px; }
}
