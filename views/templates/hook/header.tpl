<script type="text/javascript">
    {if $isOrder eq true}
    var idzTrans = {
        "cartAmount": {$trans.total|escape:'htmlall':'UTF-8'|replace:',':'.'},
        "tID": "{$trans.id|escape:'htmlall':'UTF-8'}"
    };
    {/if}
</script>