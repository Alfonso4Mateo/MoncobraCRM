@push('css')
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
:root{--ledger-primary:#002442;--ledger-ink:#191c1e;--ledger-surface:#f7f9fb;--ledger-low:#eef2f5;--ledger-muted:#687681;--ledger-shadow:0px 8px 32px rgba(25,28,30,0.04)}
body,.content-wrapper{font-family:'Inter',sans-serif;background:var(--ledger-surface);color:var(--ledger-ink)}
.main-header,.main-sidebar,.content-wrapper nav,.content-wrapper .navbar,.content-wrapper .met-tabs,.content-wrapper .plant-tabs,.content-wrapper .it-tabs{backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);background:rgba(247,249,251,.82)}
.met-heading h1,.plant-heading h1,.it-header h1,.asset-detail-head h1,.met-form-head h1,.plant-form-head h1,.it-form-heading h1,.catalog-head h1{color:var(--ledger-ink)}
.met-heading p,.plant-heading p,.it-header p,.asset-detail-head p,.met-form-head p,.plant-form-head p,.it-form-heading p,.catalog-head p{color:var(--ledger-muted)}
.met-kpi,.plant-kpi,.it-kpi,.asset-stat,.met-filters,.plant-filters,.it-toolbar,.asset-toolbar,.met-card,.plant-card,.it-card,.asset-card,.met-bottom,.plant-bottom,.it-bottom,.detail-panel,.asset-form-ux .ux-step-panel,.asset-form-ux .ux-step-actions,.met-form,.plant-form,.it-form-card,.catalog-form,.family-dialog,.detail-dialog{border:0!important;box-shadow:0px 8px 32px rgba(25,28,30,0.04)!important}
.met-btn,.plant-btn,.it-btn,.detail-btn,.met-form .btn,.plant-form .btn,.it-form-card .btn,.catalog-form .btn{border:0!important}
.met-btn-main,.plant-btn-main,.it-btn-primary,.detail-btn-main,.met-submit,.plant-submit,.it-primary,.catalog-submit,.asset-form-ux .ux-save{background:linear-gradient(110deg,#002442,#155785);box-shadow:0 10px 24px rgba(0,36,66,.22)!important;color:#fff}
.met-btn-soft,.plant-btn-soft,.it-btn-light,.detail-btn-soft,.met-form .btn-light,.plant-form .btn-light,.it-form-card .btn-light,.catalog-form .btn-light{background:#e8edf1;color:var(--ledger-primary)}
.met-filters .form-control,.plant-filters .form-control,.it-toolbar .form-control,.met-form .form-control,.plant-form .form-control,.it-form-card .form-control,.catalog-form .form-control,.detail-dialog .form-control{border:0!important;background:var(--ledger-low);color:var(--ledger-ink)}
.met-filters .form-control:focus,.plant-filters .form-control:focus,.it-toolbar .form-control:focus,.met-form .form-control:focus,.plant-form .form-control:focus,.it-form-card .form-control:focus,.catalog-form .form-control:focus,.detail-dialog .form-control:focus{border:0!important;outline:0;box-shadow:0 0 0 2px var(--ledger-primary)!important;background:#fff}
.met-kpi .label,.plant-kpi .label,.it-kpi .label,.asset-stat .label,.met-detail label,.plant-detail label,.it-detail label,.detail-summary dt,.detail-spec-list span,.met-form label,.plant-form label,.it-form-card label,.catalog-form label{font-size:12px;text-transform:uppercase;letter-spacing:.12em;color:#64727c;font-weight:700}
.met-code,.met-kpi .sub,.plant-kpi .sub,.it-kpi .sub,.met-detail small,.plant-detail small,.it-detail small,.detail-by,.event-by,.asset-form-ux .ux-required{font-size:10px;font-style:italic;color:var(--ledger-muted)}
.met-notice,.plant-notice,.it-notice{border:0!important}.met-notice{background:rgba(224,173,61,.30)}.met-notice.danger,.plant-notice.danger,.it-notice.danger{background:rgba(201,35,40,.30);color:#6e1b20}.plant-notice.blocked{background:rgba(194,128,18,.30)}
.met-meta,.detail-summary dl div,.plant-specs,.it-specs{border:0!important;background:var(--ledger-low)}
.met-tabs,.plant-tabs,.it-tabs{border:0!important}.met-tabs a.active,.plant-tabs a.active,.it-tabs a.active{border:0!important;background:#e0e3e5;padding-left:10px;padding-right:10px}
.asset-form-ux .form-control,.asset-toolbar .form-control,.events-filter{border:0!important;background:var(--ledger-low);color:var(--ledger-ink)}
.asset-form-ux .form-control:focus,.asset-toolbar .form-control:focus,.events-filter:focus{outline:0;box-shadow:0 0 0 2px var(--ledger-primary)!important;background:#fff}
</style>
@endpush
