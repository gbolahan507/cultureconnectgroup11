<!DOCTYPE html>
<html>
<head>
  <title>Registration</title>
  <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    
<?php
$type = $_GET['type'] ?? 'resident'; // default
?>

<div class="page-wrapper">

<?php
    /*
    Import  header component*/
    include "../components/header.php";
?>

<h1>Register</h1>

<!-- Toggle Buttons -->
<div class="user-type-selector">
  <button onclick="showForm('resident')">Resident</button>
  <button onclick="showForm('sme')">SME</button>
</div>

<!-- RESIDENT FORM -->
<form id="resident-form" style="<?php echo ($type == 'resident') ? 'display:block;' : 'display:none;'; ?>">
  <h2>Resident Registration</h2>

  <label>Full Name:</label>
  <input type="text" name="name" required>

  <label>Email:</label>
  <input type="email" name="email" required>

  <button type="submit">Register</button>
</form>

<!-- SME FORM -->
<form id="sme-form" style="<?php echo ($type == 'sme') ? 'display:block;' : 'display:none;'; ?>">
  <h2>SME Registration</h2>

  <label>Business Name:</label>
  <input type="text" name="business_name" required>

  <label>Business Type:</label>
  <input type="text" name="business_type" required>

  <button type="submit">Register</button>
</form>

<!-- SCRIPT -->
<script>
function showForm(type) {
  document.getElementById('resident-form').style.display =
    (type === 'resident') ? 'block' : 'none';

  document.getElementById('sme-form').style.display =
    (type === 'sme') ? 'block' : 'none';
}

// Ensure correct form loads on page open
window.onload = function() {
  showForm("<?php echo $type; ?>");
};
</script>

<?php
    /* Imports component */
    include "../components/footer.php";
?>
</div>

</body>
</html>