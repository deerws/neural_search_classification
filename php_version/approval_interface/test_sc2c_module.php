<style>
    /* Reset e configurações base */
    * {
        box-sizing: border-box;
        transition: background-color 0.3s ease, color 0.3s ease;
    }
    
    body {
        font-family: Arial, sans-serif;
        background-color: #121212;
        color: #e0e0e0;
        padding: 10px;
        margin: 0;
        line-height: 1.4;
    }

    .container {
        width: 100%;
        max-width: 100%;
        margin: 0 auto;
        padding: 0 5px;
    }

    /* Header responsivo */
    .header-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        margin-bottom: 15px;
        gap: 15px;
    }

    .logo-title-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        gap: 10px;
        margin-bottom: 15px;
    }

    .header-logo {
        width: 120px;
        height: 120px;
        max-width: 100%;
        object-fit: contain;
    }

    .title-container h1 {
        font-size: 22px;
        margin-bottom: 5px;
        color: #4a86e8;
        line-height: 1.2;
    }

    .subtitle {
        font-style: italic;
        font-size: 16px;
        color: #4A86E8;
        margin-top: 0;
    }

    /* Grid responsivo */
    .row {
        display: flex;
        flex-direction: column;
        margin: 0;
        width: 100%;
    }

    .col-md-6 {
        flex: 0 0 100%;
        max-width: 100%;
        padding: 0 5px;
        width: 100%;
    }

    /* Formulários mobile */
    .form-group {
        margin-bottom: 15px;
        width: 100%;
    }

    .form-control {
        font-size: 16px; /* Previne zoom no iOS */
        padding: 12px;
        margin-bottom: 10px;
        width: 100%;
        background-color: #333;
        color: white;
        border: 1px solid #444;
        border-radius: 4px;
    }

    /* Botões responsivos */
    .buttons-container {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin: 15px 0;
        width: 100%;
    }

    .download-buttons, .manual-buttons {
        display: flex;
        flex-direction: column;
        gap: 8px;
        width: 100%;
    }

    .download-buttons .btn, 
    .manual-buttons .btn {
        width: 100%;
        margin: 5px 0;
        padding: 12px;
        font-size: 16px;
        border-radius: 6px;
        border: none;
        cursor: pointer;
        font-weight: bold;
        text-align: center;
        text-decoration: none;
        display: inline-block;
    }

    .btn-success {
        background-color: #28a745;
        color: white;
    }

    .btn-primary {
        background-color: #1976d2;
        color: white;
    }

    .btn-info {
        background-color: #17a2b8;
        color: white;
    }

    .btn-default {
        background-color: #5a6268;
        color: white;
    }

    .btn-warning {
        background-color: #ffc107;
        color: #212529;
    }

    .btn-danger {
        background-color: #d32f2f;
        color: white;
    }

    .btn:hover {
        opacity: 0.85;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.3);
    }

    /* Tabela responsiva */
    .table-container {
        overflow-x: auto;
        max-height: 60vh;
        width: 100%;
        -webkit-overflow-scrolling: touch; /* Suaviza scroll no iOS */
        margin-bottom: 20px;
        border: 1px solid #444;
        border-radius: 6px;
    }

    table {
        min-width: 800px; /* Largura mínima para scroll horizontal */
        font-size: 14px;
        width: 100%;
        border-collapse: collapse;
        background-color: #252525;
    }

    th, td {
        padding: 10px 12px;
        white-space: nowrap;
        border: 1px solid #444;
        text-align: left;
    }

    th {
        background-color: #2a3f5f;
        color: white;
        font-weight: 600;
        position: sticky;
        top: 0;
        z-index: 10;
    }

    tr {
        background-color: #252525;
    }

    tr:hover {
        background-color: #2d2d2d;
    }

    /* Classificações em coluna única no mobile */
    .classification {
        margin-bottom: 10px;
        padding: 10px;
        font-size: 14px;
        border: 1px solid #444;
        border-radius: 6px;
        background-color: #2a2a2a;
        position: relative;
    }

    .approved {
        background-color: #1a3a1a !important;
    }

    .rejected {
        background-color: #3a1a1a !important;
    }

    .btn-xs {
        padding: 6px 10px;
        font-size: 12px;
        margin: 2px;
        min-height: auto;
    }

    .btn-approve {
        background-color: #388e3c;
        color: white;
        border: none;
        padding: 6px 12px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 13px;
        margin-right: 5px;
    }

    .btn-reject {
        background-color: #d32f2f;
        color: white;
        border: none;
        padding: 6px 12px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 13px;
    }

    .btn-approve:hover, .btn-reject:hover {
        opacity: 0.9;
        transform: translateY(-1px);
    }

    /* Painéis de instrução em coluna única */
    .panel {
        background-color: #1e1e1e;
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.6);
        padding: 15px;
        margin-bottom: 20px;
        border: 1px solid #333;
    }

    .panel .row {
        flex-direction: column;
    }

    .panel .col-md-4 {
        flex: 0 0 100%;
        max-width: 100%;
        margin-bottom: 15px;
    }

    .panel-heading {
        background-color: #2a3f5f;
        color: white;
        padding: 12px 15px;
        border-top-left-radius: 6px;
        border-top-right-radius: 6px;
        font-weight: 600;
        font-size: 16px;
        cursor: pointer;
    }

    .panel-body {
        padding: 15px;
        background-color: #252525;
        border-bottom-left-radius: 6px;
        border-bottom-right-radius: 6px;
    }

    .panel-default {
        border: 1px solid #444;
        border-radius: 6px;
        margin-bottom: 15px;
        overflow: hidden;
    }

    /* Modal responsivo */
    .modal-dialog {
        margin: 10px;
        width: calc(100% - 20px);
        max-width: 100%;
    }

    .modal-content {
        font-size: 14px;
        background-color: #1e1e1e;
        border: 1px solid #444;
        color: #e0e0e0;
    }

    .modal-header {
        border-bottom: 1px solid #444;
        background-color: #2a3f5f;
        color: white;
        padding: 15px;
    }

    .modal-body {
        background-color: #252525;
        padding: 15px;
        max-height: 70vh;
        overflow-y: auto;
    }

    .modal-footer {
        border-top: 1px solid #444;
        background-color: #252525;
        padding: 15px;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    /* Ajustes específicos para telas muito pequenas */
    @media (max-width: 480px) {
        body {
            padding: 5px;
        }
        
        .container {
            padding: 0 2px;
        }
        
        .header-logo {
            width: 100px;
            height: 100px;
        }
        
        .title-container h1 {
            font-size: 18px;
        }
        
        .subtitle {
            font-size: 14px;
        }
        
        table {
            min-width: 700px;
            font-size: 12px;
        }
        
        th, td {
            padding: 8px 10px;
        }
        
        .btn {
            padding: 10px;
            font-size: 14px;
        }
        
        .panel {
            padding: 10px;
        }
        
        .classification {
            padding: 8px;
            font-size: 13px;
        }
    }

    /* Ajustes para tablets */
    @media (min-width: 768px) and (max-width: 1024px) {
        .container {
            max-width: 95%;
            padding: 0 10px;
        }
        
        .header-logo {
            width: 150px;
            height: 150px;
        }
        
        table {
            min-width: 900px;
        }
        
        .row {
            flex-direction: row;
            flex-wrap: wrap;
        }
        
        .col-md-6 {
            flex: 0 0 50%;
            max-width: 50%;
            padding: 0 10px;
        }
        
        .download-buttons, .manual-buttons {
            flex-direction: row;
            flex-wrap: wrap;
        }
        
        .download-buttons .btn, 
        .manual-buttons .btn {
            width: auto;
            flex: 1;
            min-width: 200px;
        }
        
        .modal-dialog {
            margin: 20px auto;
            width: 90%;
        }
        
        .modal-footer {
            flex-direction: row;
            justify-content: flex-end;
        }
    }

    /* Mantém os estilos existentes para desktop */
    @media (min-width: 1025px) {
        .container {
            max-width: 98%;
            padding: 0 15px;
        }
        
        .row {
            flex-direction: row;
            margin-right: -15px;
            margin-left: -15px;
        }
        
        .col-md-6 {
            flex: 0 0 50%;
            max-width: 50%;
            padding: 0 15px;
        }
        
        .download-buttons, .manual-buttons {
            flex-direction: row;
            justify-content: center;
        }
        
        .download-buttons .btn, 
        .manual-buttons .btn {
            width: auto;
            margin: 0 5px 10px;
        }
        
        .header-container {
            flex-direction: row;
            justify-content: space-between;
            text-align: left;
        }
        
        .logo-title-container {
            flex-direction: row;
            text-align: left;
        }
        
        .buttons-container {
            align-items: flex-end;
        }
    }

    /* Melhorias de usabilidade para touch */
    .btn {
        min-height: 44px; /* Tamanho mínimo para toque */
        touch-action: manipulation; /* Melhora resposta ao toque */
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .classification .btn {
        min-height: 36px;
    }

    /* Scroll suave para elementos com overflow */
    .table-container,
    .modal-body {
        scroll-behavior: smooth;
    }

    /* Ajustes para o modal de subdomínios no mobile */
    #subdomainModal .domain-toggle {
        padding: 12px;
        font-size: 16px;
        width: 100%;
        text-align: left;
        background-color: rgba(56,104,145);
        color: white;
        border: none;
        border-radius: 5px;
        margin: 5px 0;
        cursor: pointer;
    }

    #subdomainModal .subdomain-item {
        padding: 10px;
        font-size: 14px;
        border-left: 3px solid #444;
        margin: 5px 0;
        cursor: pointer;
        transition: all 0.3s;
    }

    #subdomainModal .subdomain-item:hover {
        background-color: #333;
        border-left-color: #4a86e8;
    }

    /* Garante que inputs não zoomem no iOS */
    @media screen and (max-width: 768px) {
        input, select, textarea {
            font-size: 16px !important;
        }
    }

    /* Estilos para gráficos */
    .chart-container {
        width: 100%;
        height: 100px;
        margin-top: 10px;
    }

    /* Estilos para listas */
    ul {
        padding-left: 20px;
        margin-bottom: 0;
    }

    li {
        margin-bottom: 8px;
        color: #d0d0d0;
    }

    /* Navegação por abas */
    .nav-tabs {
        border-bottom: 1px solid #444;
    }

    .nav-tabs > li > a {
        color: #bbbbbb;
        background-color: #333;
        border: 1px solid #444;
        margin-right: 5px;
    }

    .nav-tabs > li.active > a,
    .nav-tabs > li.active > a:hover,
    .nav-tabs > li.active > a:focus {
        color: #e0e0e0;
        background-color: #252525;
        border: 1px solid #444;
        border-bottom-color: transparent;
    }

    /* Well component */
    .well {
        background-color: #2a2a2a;
        border: 1px solid #444;
        border-radius: 4px;
        padding: 15px;
        margin-top: 15px;
    }

    /* Text utilities */
    .text-center {
        text-align: center;
    }

    .text-muted {
        color: #777 !important;
    }

    /* Close button */
    .close {
        color: #e0e0e0;
        opacity: 0.8;
    }

    /* List group */
    .list-group-item {
        cursor: pointer;
        transition: all 0.2s;
        background-color: #333;
        border: 1px solid #444;
        color: #e0e0e0;
        padding: 10px 15px;
    }

    .list-group-item:hover {
        background-color: #333333;
    }

    .list-group-item.active {
        background-color: #337ab7;
        color: white;
        border-color: #337ab7;
    }

    /* Ajustes específicos para elementos da tabela em mobile */
    @media (max-width: 767px) {
        td:nth-child(1), /* Área */
        td:nth-child(2) { /* Linha de Pesquisa */
            max-width: 150px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        /* Ações - última coluna */
        td:last-child {
            min-width: 180px;
        }
        
        .btn-feedback, .btn-add-subdomain, .btn-clear-selections {
            width: 100%;
            margin-bottom: 5px;
        }
    }

    /* Melhorias de acessibilidade */
    @media (prefers-reduced-motion: reduce) {
        * {
            transition: none;
        }
    }

    /* High contrast mode support */
    @media (prefers-contrast: high) {
        body {
            background-color: #000;
            color: #fff;
        }
        
        .panel {
            border: 2px solid #fff;
        }
        
        th {
            background-color: #000;
            border: 2px solid #fff;
        }
    }
</style>
