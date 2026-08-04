<style>

body{
    background:#f5f7fb;
}

/*==============================
    MODAL
==============================*/

.contract-modal{
    width:82vw;
    max-width:82vw;
    margin:25px auto;
}

.contract-modal .modal-content{
    height:84vh;
    border:none;
    border-radius:18px;
    overflow:hidden;
    box-shadow:0 20px 60px rgba(0,0,0,.18);
}

.contract-header{

    background:linear-gradient(135deg,#2563eb,#3b82f6);

    color:#fff;

    padding:22px 30px;

    display:flex;

    justify-content:space-between;

    align-items:center;

    border:none;

}

.contract-header h3{

    margin:0;

    color:#fff;

    font-size:32px;

    font-weight:700;

}

.contract-header p{

    margin-top:5px;

    margin-bottom:0;

    color:rgba(255,255,255,.75)!important;

}

.contract-header .btn-close{

    margin:0;

    filter:brightness(0) invert(1);

    opacity:.9;

    font-size:18px;

}

.contract-header .btn-close:hover{

    opacity:1;

}

.contract-body{
    padding:25px;
    overflow-y:auto;
    height:calc(84vh - 82px);
}

/*==============================
    CARD
==============================*/

.contract-modal .card{
    border:none;
    border-radius:15px;
    box-shadow:0 5px 20px rgba(0,0,0,.05);
}

.editor-card{
    height:100%;
}

.editor-header{
    padding:18px 20px;
    border-bottom:1px solid #ececec;
}

.editor-body{
    padding:18px;
}

/*==============================
    FORM
==============================*/

.contract-modal .form-label{
    font-weight:600;
    margin-bottom:8px;
}

.contract-modal .form-control,
.contract-modal .form-select{
    height:44px;
    border-radius:10px;
    box-shadow:none!important;
}

.contract-modal textarea.form-control{
    min-height:90px;
    resize:none;
}

/*==============================
    RADIO
==============================*/

.duration-group{
    display:flex;
    gap:20px;
    flex-wrap:wrap;
}

/*==============================
    BUTTON
==============================*/

.btn-save{
    border-radius:10px;
    padding:10px 28px;
}

.btn-cancel{
    border-radius:10px;
    padding:10px 28px;
}

/*==============================
    UPLOAD
==============================*/

.upload-box{
    border:2px dashed #d8dce5;
    border-radius:12px;
    min-height:120px;
    display:flex;
    justify-content:center;
    align-items:center;
    flex-direction:column;
    transition:.25s;
    cursor:pointer;
}

.upload-box:hover{
    border-color:#198754;
    background:#f7fff9;
}

.upload-box i{
    font-size:38px;
    color:#9aa3af;
}

#previewImage{
    display:none;
    max-width:180px;
    border-radius:10px;
    margin-top:10px;
}

/*==============================
    CKEDITOR
==============================*/

.contract-editor-card{
    height:100%;
}

.editor-toolbar{
    padding:12px 16px;
    border-bottom:1px solid #ececec;
    background:#fff;
}

#editorWrapper,
#previewWrapper{
    flex:1;
}

.contract-preview{
    padding:32px;
    height:420px;
    overflow:auto;
    font-family:"Times New Roman";
    font-size:16px;
    line-height:1.8;
}

.ck.ck-editor{
    width:100%;
}

.ck-editor__editable{
    height:420px !important;
    min-height:420px !important;
    max-height:420px !important;
    font-family:"Times New Roman";
    font-size:16px;
    line-height:1.8;
}

.ck-toolbar{
    border-left:0!important;
    border-right:0!important;
    border-top:0!important;
}

#previewWrapper{
    min-height:420px;
}

/*==============================
    SCROLL
==============================*/

.contract-body::-webkit-scrollbar{
    width:8px;
}

.contract-body::-webkit-scrollbar-thumb{
    background:#d5d5d5;
    border-radius:20px;
}

.contract-body::-webkit-scrollbar-thumb:hover{
    background:#bcbcbc;
}

/*==============================
    RESPONSIVE
==============================*/

@media(max-width:1200px){

    .contract-modal{
        width:95vw;
        max-width:95vw;
    }

    .contract-modal .modal-content{
        height:92vh;
    }

}
/* =========================
   TABLE
========================= */

.contract-table{

    border-collapse:separate;

    border-spacing:0 12px;

}

.contract-table thead th{

    background:#f8fafc;

    border:none;

    color:#64748b;

    font-size:13px;

    font-weight:600;

}

.contract-table tbody tr{

    background:#fff;

    box-shadow:0 3px 12px rgba(0,0,0,.05);

    border-radius:12px;

}

.contract-table tbody td{

    vertical-align:middle;

    border:none;

    padding:18px;

}

.contract-icon{

    width:46px;

    height:46px;

    border-radius:12px;

    background:#ecfdf5;

    display:flex;

    align-items:center;

    justify-content:center;

    color:#16a34a;

    font-size:22px;

}
.btn-action{
    width:42px;
    height:42px;
    border-radius:10px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:17px;
    transition:.25s;
    background:#fff;
}

.btn-action:hover{
    transform:translateY(-2px);
    box-shadow:0 6px 16px rgba(0,0,0,.12);
}

.btn-outline-warning{
    border:1.5px solid #ffc107;
    color:#ffc107;
}

.btn-outline-warning:hover{
    background:#ffc107;
    color:#fff;
}

.btn-outline-primary{
    border:1.5px solid #3b82f6;
    color:#3b82f6;
}

.btn-outline-primary:hover{
    background:#3b82f6;
    color:#fff;
}

.btn-outline-danger{
    border:1.5px solid #ef4444;
    color:#ef4444;
}

.btn-outline-danger:hover{
    background:#ef4444;
    color:#fff;
}
/* Không để Bootstrap phá link của layout admin */
#admin-sidebar a {
    text-decoration: none !important;
}

/* Giữ link trong header không bị Bootstrap gạch chân */
header a,
nav a {
    text-decoration: none !important;
}

/* CONTRACT LIST FILTER - scoped, không ảnh hưởng layout admin */
.contract-list-filter .input-group-text{
    height:44px;
    border-color:#dee2e6;
    border-radius:10px 0 0 10px;
}
.contract-list-filter .form-control,
.contract-list-filter .form-select{
    height:44px;
    border:1px solid #dee2e6;
    border-radius:10px;
    box-shadow:none !important;
}
.contract-list-filter .input-group .form-control{
    border-left:0;
    border-radius:0 10px 10px 0;
}
.contract-list-filter .form-control:focus,
.contract-list-filter .form-select:focus{
    border-color:#86b7fe;
    box-shadow:0 0 0 .2rem rgba(13,110,253,.12) !important;
}
.contract-list-filter .btn{
    height:44px;
    border-radius:10px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:6px;
}
</style>

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

<style>
/*
|--------------------------------------------------------------------------
| BẢO VỆ LAYOUT ADMIN KHỎI BOOTSTRAP
|--------------------------------------------------------------------------
| Giữ nguyên Bootstrap cho contract form/modal/list.
| Chỉ khôi phục đúng Sidebar + Header theo Tailwind layout hiện tại.
*/

/* ==================== SIDEBAR ==================== */
#admin-sidebar {
    width: 18rem !important;              /* w-72 */
    background-color: #fff !important;
    border-right: 1px solid rgb(226 232 240) !important;
    font-family: "Instrument Sans", sans-serif !important;
    line-height: 1.5 !important;
}

#admin-sidebar *,
#admin-sidebar *::before,
#admin-sidebar *::after {
    box-sizing: border-box !important;
}

#admin-sidebar a {
    text-decoration: none !important;
}

#admin-sidebar p,
#admin-sidebar span,
#admin-sidebar summary,
#admin-sidebar a {
    font-family: inherit !important;
}

#admin-sidebar p {
    margin: 0 !important;
}

#admin-sidebar nav {
    display: block !important;
}

#admin-sidebar summary {
    list-style: none !important;
}

#admin-sidebar summary::-webkit-details-marker {
    display: none !important;
}

/* Brand */
#admin-sidebar > div:first-child {
    height: 4rem !important;              /* h-16 */
    padding-left: 1.25rem !important;
    padding-right: 1.25rem !important;
}

#admin-sidebar > div:first-child > a {
    display: flex !important;
    align-items: center !important;
    gap: .75rem !important;
}

#admin-sidebar > div:first-child > a > span:first-child {
    display: flex !important;
    width: 2.5rem !important;
    height: 2.5rem !important;
    align-items: center !important;
    justify-content: center !important;
    border-radius: .5rem !important;
    background-color: rgb(79 70 229) !important;
    color: #fff !important;
    font-size: 1.25rem !important;
    line-height: 1.75rem !important;
    font-weight: 700 !important;
}

#admin-sidebar > div:first-child > a > span:last-child > span:first-child {
    display: block !important;
    color: rgb(15 23 42) !important;
    font-size: .875rem !important;
    line-height: 1.25rem !important;
    font-weight: 700 !important;
}

#admin-sidebar > div:first-child > a > span:last-child > span:last-child {
    display: block !important;
    color: rgb(100 116 139) !important;
    font-size: .75rem !important;
    line-height: 1rem !important;
}

/* Menu area */
#admin-sidebar nav {
    padding: 1.25rem 1rem !important;
}

#admin-sidebar nav > p {
    padding-left: .75rem !important;
    padding-right: .75rem !important;
    color: rgb(148 163 184) !important;
    font-size: .75rem !important;
    line-height: 1rem !important;
    font-weight: 600 !important;
    text-transform: uppercase !important;
    letter-spacing: .025em !important;
}

#admin-sidebar nav > div {
    margin-top: .75rem !important;
}

#admin-sidebar nav details {
    margin-bottom: .5rem !important;
    border-radius: .5rem !important;
}

#admin-sidebar nav details > summary {
    display: flex !important;
    cursor: pointer !important;
    align-items: center !important;
    gap: .75rem !important;
    padding: .625rem .75rem !important;
    border-radius: .5rem !important;
    font-size: .875rem !important;
    line-height: 1.25rem !important;
    font-weight: 600 !important;
}

#admin-sidebar nav details > summary > i:first-child {
    font-size: 1.25rem !important;
    line-height: 1.75rem !important;
}

#admin-sidebar nav details > summary > span {
    flex: 1 1 0% !important;
}

#admin-sidebar nav details > div {
    margin-top: .25rem !important;
    padding-left: 2.5rem !important;
}

#admin-sidebar nav details > div > a {
    display: block !important;
    margin-bottom: .25rem !important;
    padding: .5rem .75rem !important;
    border-radius: .5rem !important;
    font-size: .875rem !important;
    line-height: 1.25rem !important;
}

/* ==================== HEADER ==================== */
body > .min-h-screen > div.flex.min-w-0.flex-1.flex-col > header {
    position: sticky !important;
    top: 0 !important;
    z-index: 20 !important;
    border-bottom: 1px solid rgb(226 232 240) !important;
    background: rgba(255,255,255,.95) !important;
    font-family: "Instrument Sans", sans-serif !important;
}

body > .min-h-screen > div.flex.min-w-0.flex-1.flex-col > header *,
body > .min-h-screen > div.flex.min-w-0.flex-1.flex-col > header *::before,
body > .min-h-screen > div.flex.min-w-0.flex-1.flex-col > header *::after {
    box-sizing: border-box !important;
}

body > .min-h-screen > div.flex.min-w-0.flex-1.flex-col > header > div {
    display: flex !important;
    height: 4rem !important;              /* h-16 */
    align-items: center !important;
    justify-content: space-between !important;
    gap: 1rem !important;
    padding-left: 2rem !important;
    padding-right: 2rem !important;
}

body > .min-h-screen > div.flex.min-w-0.flex-1.flex-col > header p,
body > .min-h-screen > div.flex.min-w-0.flex-1.flex-col > header h1 {
    margin: 0 !important;
    font-family: inherit !important;
}

body > .min-h-screen > div.flex.min-w-0.flex-1.flex-col > header h1 {
    color: rgb(15 23 42) !important;
    font-size: 1.125rem !important;
    line-height: 1.75rem !important;
    font-weight: 600 !important;
}

body > .min-h-screen > div.flex.min-w-0.flex-1.flex-col > header p.text-xs {
    font-size: .75rem !important;
    line-height: 1rem !important;
}

body > .min-h-screen > div.flex.min-w-0.flex-1.flex-col > header p.text-sm {
    font-size: .875rem !important;
    line-height: 1.25rem !important;
}

body > .min-h-screen > div.flex.min-w-0.flex-1.flex-col > header button {
    font-family: inherit !important;
}

body > .min-h-screen > div.flex.min-w-0.flex-1.flex-col > header #admin-user-menu-button {
    display: flex !important;
    width: 2.5rem !important;
    height: 2.5rem !important;
    padding: 0 !important;
    align-items: center !important;
    justify-content: center !important;
    border: 0 !important;
    border-radius: 9999px !important;
    background-color: rgb(79 70 229) !important;
    color: #fff !important;
    font-size: .875rem !important;
    line-height: 1.25rem !important;
    font-weight: 600 !important;
}

/* Desktop content offset must remain identical to shared admin layout */
@media (min-width: 1024px) {
    body > .min-h-screen > div.flex.min-w-0.flex-1.flex-col {
        padding-left: 18rem !important;    /* lg:pl-72 */
    }
}

/* Mobile: do not force desktop header padding */
@media (max-width: 1023.98px) {
    body > .min-h-screen > div.flex.min-w-0.flex-1.flex-col > header > div {
        padding-left: 1rem !important;
        padding-right: 1rem !important;
    }
}
</style>
@endpush
