# Mysqli Easy Class
Mysqli Easy to use PHP Class, from PHPSnipe framework.

Include it and use it like this

<code>
$db = new Database();
$sql = "select * from settings";
$db->query($sql);
$i = 0;
while ($db->next()) {
	$field = $login->rs['name'];
	$settings->$field = $login->rs['value'];
	$settings->description[$field] = $login->rs['description'];
	$i++;
}
</code>
