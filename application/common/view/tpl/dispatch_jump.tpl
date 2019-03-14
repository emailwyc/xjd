{__NOLAYOUT__}<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>{:__('Warning')}</title>
</head>
<body>
{if $url}
    <script type="text/javascript">
        location.href = "{$url}";
    </script>
{/if}
</body>
</html>