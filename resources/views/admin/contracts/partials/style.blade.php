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

.card{
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

.form-label{
    font-weight:600;
    margin-bottom:8px;
}

.form-control,
.form-select{
    height:44px;
    border-radius:10px;
    box-shadow:none!important;
}

textarea.form-control{
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

</style>