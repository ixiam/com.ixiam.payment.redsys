<div class="crm-block crm-form-block">
  <table class="form-layout">
    <tr class="crm-redsys-form-block-ipn_http">
      <td width="20%">
        {$form.ipn_http.label}
      </td>
      <td>
        {$form.ipn_http.html}
        <br>
        <span class="description">Redsys doesn't support ipn callbacks using SSL shared certificate in multiples websites with a single IP. In that case you must force to use http protocol in ipn callback url</span>  
      </td>
    </tr>
    <tr class="crm-redsys-form-block-merchant_terminal">
      <td width="20%">
        {$form.merchant_terminal.label}
      </td>
      <td>
        {$form.merchant_terminal.html}
        <br>
        <span class="description">Default merchant terminal number ("1" if not defined)</span>
      </td>
    </tr>

    {foreach key=property item=terminal from=$form name=terminals}
      {if $smarty.foreach.terminals.first}
        <tr>
          <th colspan="2">Merchant terminal numbers for specific payment prcessors</th>
        </tr>
      {/if}

      {if $property|strpos:'merchant_terminal_' === 0}
        <tr class="crm-redsys-form-block-merchant_terminal">
          <td width="20%">
            {$terminal.label}
          </td>
          <td>
            {$terminal.html}
          </td>
        </tr>
      {/if}
    {/foreach}

  </table>
  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>
</div>

