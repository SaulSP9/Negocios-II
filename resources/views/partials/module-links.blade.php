@if(auth()->user()->hasPermission('erp.read'))<a href="/erp">ERP</a>@endif
@if(auth()->user()->hasPermission('crm'))<a href="/crm">CRM</a>@endif
@if(auth()->user()->hasPermission('scm.read'))<a href="/scm">SCM</a>@endif
