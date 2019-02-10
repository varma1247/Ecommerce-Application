<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors','On');
$catxml=file_get_contents('http://sandbox.api.ebaycommercenetwork.com/publisher/3.0/rest/CategoryTree?apiKey=78b0db8a-0ee1-4939-a2f9-d3cd95ec0fcc&visitorUserAgent&visitorIPAddress&trackingId=7000610&categoryId=72');
$cxml=new SimpleXMLElement($catxml);
if(!isset($_SESSION['items'])){
  $_SESSION['items'] = array();
};
if (isset($_GET['delete'])) {
unset($_SESSION['basket'][$_GET['delete']]);
}
if (isset($_GET['clear'])&&$_GET['clear']==1) {
  unset($_SESSION['basket']);
}
if (isset($_GET['category']) && isset($_GET['search'])) {
  $_SESSION['category']=$_GET['category'];
}
?>
<!DOCTYPE html>
<html>
<head><title>Buy Products</title>
  <style>
table, td, th {
    border: 1px solid #ddd;
    text-align: left;
}
table {
    border-collapse: collapse;
    width: 100%;
    background-color: #dee5f2;
}
th, td {
    padding: 15px;
}
select {
    border: none;
    border-radius: 10px;
    background-color: #F1F0FF;
    font-size:15px;
    text-align: center;
}
input[type=text]{
  border: none;
  border-radius: 10px;
  font-size: 15px;
  background-color: #F1F0FF;
}
input[type=submit]{
  padding: 5px;
  border: none;
  border-radius: 10px;
  font-size: 15px;
  background-color: #009dff;
  color: white;
  font-weight: bold;
}
tr{
  border-radius:5px;
}
</style>
</head>
<body>
<div style="margin-top: 15px; margin-bottom:15px; text-align:center; font-size:20px;">
  <form action='buy.php' method='GET' style='display:inline;float:right;position:relative;left:-200px;'>
  <input type='hidden' name='clear' value='1'>
  <input type='submit' value='Empty Cart' style="background-color:#d9534f">
  </form>
<form class="" action="buy.php" method="GET">
  <label>Category:</label>
  <select name="category" value='72'>
    <?php
    $cat=$cxml->category;
    echo "<option value='".$cxml->category['id']."' selected='selected'>$cat->name</option>";
    echo "<optgroup label='$cat->name:'></optgroup>";
    foreach ($cat->categories->category as $b){
    // print($b->name);
    $id=$b['id'];
    $innercat=file_get_contents('http://sandbox.api.ebaycommercenetwork.com/publisher/3.0/rest/CategoryTree?apiKey=78b0db8a-0ee1-4939-a2f9-d3cd95ec0fcc&visitorUserAgent&visitorIPAddress&trackingId=7000610&categoryId='.$id);
    $innercatxml=new SimpleXMLElement($innercat);
    $catstring='';
    foreach ($innercatxml->category->categories->category as $inner){
      $innerid=$inner['id'];
      if (isset($_SESSION['category'])&&($innerid==$_SESSION['category'])) {
        $catstring=$catstring."<option value='".$innerid."' selected='selected'>".(string)$inner->name."</option>";
      }
      else {
        $catstring=$catstring."<option value='".$innerid."'>".(string)$inner->name."</option>";
      }
    };
    echo "<option value='".$id."'>".(string)$b->name."</option>";
    echo "<optgroup label='".(string)$b->name.":'>".$catstring."</optgroup>";
  }
    ?>
  </select>
  <label>Search Keywords:</label>
  <input type="text" name="search" style="text-align:center">
  <input type="submit" name="" value="Search">
</form>
  </div>
<div style="height:50vh;overflow-y:scroll;width:80%;margin:auto">
  <table >
    <tbody>
  <?php
  if (isset($_GET['category']) && isset($_GET['search'])) {
    echo "<p style='background-color:#009dff; text-align:center; padding:10px; font-size:20px; border-radius:10px; font-weight:bold;color:white'>Products</p>";
    echo "<p style='text-align:center'>Scroll to view all</p>";
    unset($_SESSION['items']);
    $category=$_GET['category'];
    $search= implode("+", explode(" ",$_GET['search']));
    $products=file_get_contents("http://sandbox.api.ebaycommercenetwork.com/publisher/3.0/rest/GeneralSearch?apiKey=78b0db8a-0ee1-4939-a2f9-d3cd95ec0fcc&trackingId=7000610&categoryId=".$category."&keyword=".$search."&numItems=20");
    $pxml = new SimpleXMLElement($products);
    if (!empty($pxml->categories->category->items->offer)) {
    foreach ($pxml->categories->category->items->offer as $offer) {
      echo "<tr>";
      echo "<td><a href='buy.php?buy=".$offer['id']."'>".(string)$offer->name."</a></td>";
      echo "<td>".(string)$offer->basePrice."$</td>";
      echo "<td>".(string)$offer->description."</td>";
      echo "</tr>";
      $_SESSION['items'][(string)$offer['id']] = array('name' => (string)$offer->name, 'price' => (string)$offer->basePrice);
    }
  }
  if (empty($pxml->categories->category->items->offer)) {
    echo "<p style='margin-top:75px;text-align:center'>No Products</p>";
    $_SESSION['items']=[[]];
  }
    // print_r($_SESSION['items']);
  }
   ?>
  </tbody>
 </table>
</div>
<?php
if(!isset($_SESSION['basket'])){
  $_SESSION['basket'] = array();
};
   if (isset($_GET['buy'])&&!array_key_exists($_GET['buy'],$_SESSION['basket'])&&!empty($_SESSION['items'])) {
     $_SESSION['basket'][$_GET['buy']]=  $_SESSION['items'][$_GET['buy']];
   }
if (isset($_SESSION['basket'])&&isset($_SESSION['items'])&&!empty(($_SESSION['basket']))) {
  echo "<div style='margin-top:20px; width:80%; margin:auto'>
    <p style='background-color:#009dff; text-align:center; padding:10px; font-size:20px; border-radius:10px; font-weight:bold; color:white'>Shopping Cart</p>
    <p style='text-align:center'>
      Scroll to view all
    </p>";
    // if (sizeof($_SESSION['basket'])==0) {
    //   echo "<p style='margin-top:75px;text-align:center'>No Products in cart</p>";
    // }
  echo "</div>";
}
?>
<div style="">
  <p style="text-align:center; width:10%; margin:auto; background-color:aqua; border-radius:15px;font-weight:bold; color:red">
  <?php
  $total=0;
  if (isset($_SESSION['basket'])&&isset($_SESSION['items'])) {
    foreach ($_SESSION['basket']as $key => $value) {
      $total=$total+$value['price'];
    }
    }
    if (!$total==0) {
      echo "Total: ".$total." $";
    }
   ?>
 </p>
</div>
<div style="height:20vh; width:80%; margin:auto;margin-top:20px; overflow-y:scroll; text-align:center">
  <table style="margin:auto; width:80%;" border="1">
   <tbody>
     <?php
       if (isset($_SESSION['basket'])&&isset($_SESSION['items'])&&!empty(($_SESSION['basket']))) {
         foreach ($_SESSION['basket']as $key => $value) {
           echo "<tr>";
           echo "<td>".$value['name']."</td>";
           echo "<td>".$value['price']."$</td>";
           echo "<td><a href='buy.php?delete=".$key."'>delete</a></td>";
           echo "</tr>";
         }
         }
     ?>
   </tbody>
  </table>
</div>
</body>
</html>
