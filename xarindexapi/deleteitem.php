<?php
function keywords_indexapi_deleteitem(Array $args=array())
{
    if (empty($args['id']) || !is_numeric($args['id']))
        $invalid[] = 'id';

    if (!empty($invalid)) {
        $msg = 'Invalid #(1) for #(2) module #(3) function #(4)()';
        $vars = array(implode(', ', $invalid), 'keywords', 'indexapi', 'deleteitem');
        throw new BadParameterException($vars, $msg);
    }

    return xarMod::apiFunc('keywords', 'index', 'deleteitems', $args);

}
?>