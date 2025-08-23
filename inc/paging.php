<?php
    function did_paging(&$query,$cnt=10){

        $count=$cnt;
        $page = 0;
        if(isset($_POST['paging']) or isset($_POST['__paging']))
        $page = isset($_POST['paging'])?array_keys($_POST['paging'])[0]:$_POST['__paging'];
        $max_page=intval(count($query)/$count);
        if((count($query)%$count)==0)
        --$max_page;
        $query = array_slice($query,$count*$page,$count);
        ob_start();
?>
<style>
.pagination li{
	display: inline-block;
}

.pagination input{
	display: inline-block;
	vertical-align: baseline;
	min-width: 30px;
	min-height: 30px;
	margin: 0;
	padding: 0 4px;
	font-size: 16px;
	line-height: 1.625;
	text-align: center;

	color: #2271b1;
	border-color: #2271b1;
	background: #f6f7f7;
	border-width: 1px;
	border-style: solid;
	-webkit-appearance: none;
	border-radius: 3px;
	white-space: nowrap;
	box-sizing: border-box;
}

.pagination input[disabled]{
	color: #a7aaad !important;
	border-color: #dcdcde !important;
	background: #f6f7f7 !important;
	box-shadow: none !important;
	cursor: default;
	transform: none !important;
}

</style>
    <ul class="pagination justify-content-center mb-0">
        <li class="page-item"><input type="submit" class="page-link" name="paging[<?php echo '0'; ?>]" class="btn" value="«" <?php if( absint( $page ) == 0 )echo "disabled" ?>/></li>
        <li class="page-item"><input type="submit" class="page-link" name="paging[<?php if( absint( $page ) == 0 )echo "0"; else echo absint( $page )-1; ?>]" class="btn" value="‹" <?php if( absint( $page )== 0 )echo "disabled" ?>/></li>

<?php
		$rng = 3;
		$mi1 = $page<$rng?0:$page - $rng;
        $mi2 = $page+$rng;
        $mi3 = $mi2>$max_page?$max_page:$max_page - $rng;
        $mi2 = $mi2>$mi3?$mi3:$mi2;

        $arr = array_merge(range(0,($page<$rng?$page:$rng-1)),range($mi1,$mi2),range($mi3,$max_page));
        $arr = array_unique($arr);
        $pre = $i=0;
        foreach($arr as $i){
            echo (absint( $pre ) == absint($i) ?'':'...')."\t<input type='submit' class='page-link' value='".(absint($i)+1)."' name='paging[" . absint($i) . "]'".( absint($i) == absint( $page )?' disabled':'').">\n";
            $pre = $i+1;
        }
?>
        <li class="page-item"><input type="submit" class="page-link" name="paging[<?php if( absint( $page ) < absint( $max_page ) )echo absint( $page )+1;else echo absint( $max_page ); ?>]" class="btn" value="›" <?php if(absint(absint($page))==$max_page)echo "disabled" ?>/></li>
        <li class="page-item"><input type="submit" class="page-link" name="paging[<?php echo absint( $max_page ); ?>]" class="btn" value="»" <?php if( absint( $page ) == absint( $max_page ) )echo "disabled" ?>/></li>
        <input type="hidden" name="__paging" value="<?php echo absint( $page ) ?>" />
    </ul>
<?php
        return ob_get_clean();
    }
?>