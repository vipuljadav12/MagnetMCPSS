Enter Address: <input type="text" id="address" value="">&nbsp;<input type="text" id="zip" value="">&nbsp;<button type="button" onclick="fetchAddress()">Search</button>
<script type="text/javascript">
	function fetchAddress()
	{
		var val = document.getElementById("address").value;
		var val1 = document.getElementById("zip").value;
		document.location.href = "check.php?addr="+val+"&zip="+val1;
	}
</script>