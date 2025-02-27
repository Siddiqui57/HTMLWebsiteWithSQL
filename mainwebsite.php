<html>
<head>
  <title>467 FINAL PROJECT</title>
  <link rel="stylesheet" type="text/css" href="style.css">
  <script src="script.js"></script>
</head>
<body>

<form method ="POST" action ="">

<?php
session_start();

//connect to databse
try{
  $dsn = "mysql:host=courses;dbname=z1977773";
  $pdo = new PDO($dsn, "z1977773", "2004Apr14");
}
catch(PDOexception $e){
  echo "<p>Connection to database failed: " . $e->getMessage() . "</p>";
}

$productsResult = $pdo->query("SELECT number, description, weight, price, pictureURL FROM Parts"); //get part info from database
$products = $productsResult->fetchAll();

$weightResult = $pdo->query("SELECT * FROM weight_brackets"); //get shipping and handling info from database
$weight_brackets = $weightResult->fetchAll();

$inventoryResult = $pdo->query("SELECT * FROM inventory"); //get inventory information from database
$inventory = $inventoryResult->fetchAll();

//craete primary order list array on first run
if(!isset($_SESSION['orders']))
{
  $_SESSION['orders'] = array();
}

//create primary shipping cart array on first run
if(!isset($_SESSION['shopping_cart']))
{
  $_SESSION['shopping_cart'] = array();
}

//create employee login variables on first run
if(!isset($_SESSION['loggedin']))
{
  $_SESSION['loggedin'] = false;
  $_SESSION['isrec'] = false;
  $_SESSION['isship'] = false;
  $_SESSION['isadmin'] = false;
}

if(isset($_POST['submit'])) //employee login attempt was submitted
{
  if($_POST['login_password'] == "recpass") //login as receiving desk
  {
    $_SESSION['loggedin'] = true;
    $_SESSION['isrec'] = true;
  }
  else if($_POST['login_password'] == "shippass") //login as shipping team
  {
    $_SESSION['loggedin'] = true;
    $_SESSION['isship'] = true;
  }
  else if($_POST['login_password'] == "adminpass") //login as admin
  {
    $_SESSION['loggedin'] = true;
    $_SESSION['isadmin'] = true;
  }

  //incorrect password attempts do nothing
}

if(!$_SESSION['loggedin']) //displays login button
{
  echo "<input type=\"password\" value=\"\" name=\"login_password\" placeholder='Employee Login'>";
  echo "<input type=\"submit\" name=\"submit\" value=' '>";
}

if($_SESSION['loggedin'])
{
  if($_SESSION['isrec']) //receiving desk
  {
    echo "<h2>Welcome to the rec. terminal<h2>"; //header
    echo "<input type='submit' name='logout' value='Logout'>"; //log out of rec
    if(isset($_POST['logout']))
    {
      $_SESSION['isrec'] = false;
      $_SESSION['loggedin'] = false;
      header("Refresh:0");
    }

    //print table headers
    echo "<h2>Items in Catalog</h2>";
    echo "<table>";
    echo "<tr>";
    echo "<th>Number</th>";
    echo "<th>Quantity</th>";
    echo "<th>Operation</th>";
    echo "<th>Amount</th>";
    echo "<th>Action</th>";
    echo "</tr>";

    foreach($inventory as $item)
    {
      echo "<tr>";
      echo "<td>" . $item['number'] . "</td>"; //item number
      echo "<td>" . $item['quantity'] . "</td>"; //quantity on hand
      echo "<td>";
        echo "<select name='operation'>";
        echo "<option value='+'>Increase</option>"; //increase inventory
        echo "<option value='-'>Decrease</option>"; //decrease inventory
        echo "</select>";
      echo "</td>";
      echo "<td><input type='number' name='amount'></td>"; //quantity to increase or decrease
      echo "<td><button type='submit' name='update' value='{$item['number']}'>Update</button></td>";
      echo "</tr>";
    }
    echo "</table>";

    if(isset($_POST['update']))
    {
      if($_POST['operation'] == '+')
      {
        $pdo->query("UPDATE inventory SET quantity = quantity + '{$_POST['amount']}' WHERE number = '{$_POST['update']}'"); //increase item inventory
      }
      else if($_POST['operation'] == '-')
      {
        $pdo->query("UPDATE inventory SET quantity = GREATEST(0, quantity - '{$_POST['amount']}') WHERE number = '{$_POST['update']}'"); //decrease item inventory
      }
      header("Refresh:0");
    }
  }
  else if($_SESSION['isship']) //shipping team
  {
    echo "<h2>Welcome to the shipping terminal<h2>";
    echo "<input type='submit' name='logout' value='Logout'>";
    if(isset($_POST['logout']))
    {
      $_SESSION['isship'] = false;
      $_SESSION['loggedin'] = false;
      header("Refresh:0");
    }

    $orders = $pdo->query("SELECT tracking_ID FROM Orders WHERE status = 'Processing'"); //get list of orders that are still processing
    $allorder = $orders->fetchAll();

    $headers = $pdo->query("DESCRIBE Orders"); //table headers
    $header_info = $headers->fetchAll();

    $headers2 = $pdo->query("DESCRIBE Prod_Ordered"); //table headers
    $header_info2 = $headers2->fetchAll();

    echo "<h2>Fullfill Order</h2>";
    echo "<p><select name=\"orderselect\"/></p>"; //drop down select with all orders that are processing
    echo "<option value=\"\" selected disabled hidden>Select Order</option>";
    foreach($allorder as $order)
    {
       echo "<option value=\"$order[0]\">" . $order[0] . "</option>";
    }
    echo "</select>";

    echo "<input type=\"submit\" name=\"select\" value=\"Select\">";

    if(isset($_POST["orderselect"]))
    {
      $_SESSION["tracking_id"] = $_POST["orderselect"];
      $order = $pdo->query("SELECT * FROM Orders WHERE tracking_id = '{$_SESSION['tracking_id']}'"); //get all information about selected order
      $order_info = $order->fetchAll();

      $order2 = $pdo->query("SELECT * FROM Prod_Ordered WHERE tracking_id = '{$_SESSION['tracking_id']}'"); //get all items in order
      $order_items = $order2->fetchAll();

      //print table to display order information
      echo "<p><table>";
      foreach($header_info as $header)
      {
        echo "<th>" . $header[0] . "</th>";
      }

      for($i=0; $i<$order->rowCount(); ++$i)
      {
        echo "<tr>";
        for($j=0; $j<$order->columnCount(); ++$j)
        {
          echo "<td>" . $order_info[$i][$j] . "</td>";
        }
        echo "</tr>";
      }
      echo "</table></p>";

      //print table to display items in order
      echo "<p><table>";
      foreach($header_info2 as $header)
      {
        echo "<th>" . $header[0] . "</th>";
      }

      for($i=0; $i<$order2->rowCount(); ++$i)
      {
        echo "<tr>";
        for($j=0; $j<$order2->columnCount(); ++$j)
        {
          echo "<td>" . $order_items[$i][$j] . "</td>";
        }
        echo "</tr>";
      }
      echo "</table></p>";

      echo "<p>";
      echo "<input type=\"radio\" name=\"fullfill\" value=\"Processing\"/>Processing"; //set status as processing
      echo "<input type=\"radio\" name=\"fullfill\" value=\"Shipped\"/>Shipped"; //set status as shipped
      echo "<input type=\"radio\" name=\"fullfill\" value=\"Delivered\"/>Delivered"; //set status as delivered
      echo "</p>";

      echo "<input type=\"submit\" name=\"fullfill_order\" value=\"Change Order Status\">";
    }

    if(isset($_POST["fullfill"]))
    {
      $status = $_POST["fullfill"];
      $new_message = $_POST["new_message"];
      $tracking_id = $_SESSION["tracking_id"];

      $pdo->exec("UPDATE Orders SET status='$status' WHERE tracking_ID = '$tracking_id'"); //update order status
      header("Refresh:0");
    }

    $fullfilled_orders = $pdo->query("SELECT * FROM Orders WHERE status='Shipped' OR status='Delivered';"); //get all orders that are shipped or delivered
    $all_fullfilled_order = $fullfilled_orders->fetchAll();

    //print table of all fullfilled orders
    echo "<h2>Fullfilled Orders</h2>";
    echo "<p><table>";
    foreach($header_info as $header)
    {
      echo "<th>" . $header[0] . "</th>";
    }

    for($i=0; $i<$fullfilled_orders->rowCount(); ++$i)
    {
      echo "<tr>";
      for($j=0; $j<$fullfilled_orders->columnCount(); ++$j)
      {
        echo "<td>" . $all_fullfilled_order[$i][$j] . "</td>";
      }
      echo "</tr>";
    }
    echo "</table></p>";
  }
  else if($_SESSION['isadmin']) //admin
  {
    echo "<h2>Welcome to the admin terminal<h2>"; //header
    echo "<input type='submit' name='logout' value='Logout'>"; //log out of admin
    if(isset($_POST['logout']))
    {
      $_SESSION['isadmin'] = false;
      $_SESSION['loggedin'] = false;
      header("Refresh:0");
    }

    echo "<h2>Search Orders</h2>";
    echo "<p>Search for Orders:</p>";

    echo "<input type='number' name='searchID'>";

    echo "<p>";
    echo "<input type='radio' name='status' value='Processing'/>Processing";
    echo "<input type='radio' name='status' value='Shipped'/>Shipped";
    echo "<input type='radio' name='status' value='Delivered'/>Delivered";
    echo "</p>";

    echo "<input type='submit' name='search' value='Search'>";

    if(isset($_POST['search']))
    {
      $orderResult = $pdo->query("SELECT * FROM Orders WHERE tracking_ID = '{$_POST['searchID']}')");
      $order = $orderResult->fetchAll();

      $headers = $pdo->query("DESCRIBE Orders");
      $header_info = $headers->fetchAll();

      echo "<h2>Fullfilled Orders</h2>";
      echo "<p><table>";
      foreach($header_info as $header)
      {
        echo "<th>" . $header[0] . "</th>";
      }

      for($i=0; $i<$orderResult->rowCount(); ++$i)
      {
        echo "<tr>";
        for($j=0; $j<$orderResult->columnCount(); ++$j)
        {
          echo "<td>" . $order[$i][$j] . "</td>";
        }
        echo "</tr>";
      }
      echo "</table></p>";
    }

    echo "<h2>Update Shipping & Handling Charges</h2>";
    //display table of all current weight brackets and their shipping charges
    echo "<table>";
    echo "<tr><th>Weight Class</th><th>Minimum Weight</th><th>Maximum Weight</th><th>Current Charge</th></tr>";
    foreach($weight_brackets as $bracket)
    {
      echo "<tr>";
      echo "<td>" . $bracket['bracket_name'] . "</td>"; //weight class name
      echo "<td>" . $bracket['min_weight'] . "</td>"; //minimum weight
      echo "<td>" . $bracket['max_weight'] . "</td>"; //maximum weight
      echo "<td>$" . $bracket['charge'] . "</td>"; //cost associated with weight
      echo "</tr>";
    }
    echo "</table>";

    //menu to select a weight bracket and what to update
    echo "<h4>DISCLAIMER: As admin, you are able to update the charges that customers will have applied to their orders based on the total weight of the order. " .
              "The price added on is based on a minimum and maximum weight for each of the 4 weight classes. It is your job to make sure these 3 rules are followed:<br>" .
              "1) No gaps are present in between the weight classes. This means, for example, the \"light\" weight class cannot have a max weight of 10 while " .
              "the \"medium\" weight class has a min weight of 15.<br>" .
              "2) The minimum must be less than the maximum for each class.<br>" .
              "3) There is no overlap between the weight classes and their respective min and max weights. This means, the \"light\" weight class cannot have a max " .
              "weight of 10 while the \"medium\" weight class has a min weight of 8.</h4>";
    if(!isset($_POST['update']))
    {
      echo "<p>Select Weight Bracket to Update: </p>";
      echo "<select name='bracketlist'>"; //selection for weight brackets
      echo "<option value='' selected>";
      foreach ($weight_brackets as $bracket)
      {
        echo "<option value='{$bracket['bracket_name']}'>" . $bracket['bracket_name'] . "</option>"; //display each weight bracket as an option
      }
      echo "</select>";

      echo "<p>Select What to Update: </p>";
      echo "<select name='option'>"; //selection for what to update
      echo "<option value='' selected>"; //blank value selected by default
      echo "<option value='price'>Update Pricing</option>"; //update price
      echo "<option value='weight'>Update Weight Brackets</option>"; //update min and max weight for selected bracket
      echo "</select>";

      echo "<p><input type='submit' name='update' value='Start'></p>";
    }

    if(isset($_POST['update']))
    {
      $_SESSION['bracket'] = $_POST['bracketlist'];
      echo "<p>Updating " . $_SESSION['bracket'] . "</p>"; //displays what weight bracket is being updated
      if($_POST['option'] == 'price') //updating price
      {
        echo "<p><input type='number' name='newprice' step='.01' min=0></p>"; //enter new price for weight bracket
        echo "<input type='submit' name='updateprice' value='Update Price'>";
      }
      else if($_POST['option'] == 'weight') //updating weight
      {
        echo "<p>Minimum Weight: <input type='number' name='newmin'></p>"; //enter new min weight for weight bracket
        echo "<p>Maximum Weight: <input type='number' name='newmax'></p>"; //etner new max weight for weight bracket
        echo "<input type='submit' name='updateweight' value='Update Weight'>";
      }
    }

    if(isset($_POST['updateprice']))
    {
      $price = $_POST['newprice'];
      $bracket = $_SESSION['bracket'];
      $pdo->query("UPDATE weight_brackets SET charge = '$price' WHERE bracket_name = '$bracket'"); //update price
      header("Refresh:0");
    }
    else if(isset($_POST['updateweight']))
    {
      $minweight = $_POST['newmin'];
      $maxweight = $_POST['newmax'];
      $bracket = $_SESSION['bracket'];
      $pdo->query("UPDATE weight_brackets SET min_weight = '$minweight', max_weight = '$maxweight' WHERE bracket_name = '$bracket'"); //update weights
      header("Refresh:0");
    }
  }
}

if(!$_SESSION['loggedin'])
{
echo "<div id='banner'>"; //top portion of site including site name and page selection buttons
echo "<h1 id='akmshop'>AKMShop</h1>"; //site name

echo "<h2>";
echo "<button type=\"submit\" class=\"pageselect\" name=\"catalog\">Product Catalog</button>"; //button to go to store catalog page
echo "<button type=\"submit\" class=\"pageselect\" name=\"cart\">Shopping Cart</button>"; //button to go to cart page
echo "<h2>";

echo "</div>";

//switch from cart page to catalog page
if(isset($_POST['catalog']))
{
  $_SESSION['catalog'] = true;
  unset($_SESSION['cart']);
}

//switch from catalog page to cart page
if(isset($_POST['cart']))
{
  $_SESSION['cart'] = true;
  unset($_SESSION['catalog']);
}

if(isset($_SESSION['catalog']))
{
//****************************************//
//*********DISPLAY STORE CATALOG**********//
//****************************************//
if(!isset($_POST['checkout']) && !isset($_POST['Submit_Order']))
{
  //create table to display all parts and information
  echo "<table>";
  echo "<tr><th>Item Number</th><th>Description</th><th>Price</th><th>Weight</th><th>Picture</th><th>Quantity</th><th>Action</th></tr>";
  foreach ($products as $product)
  {
    $result = $pdo->query("SELECT quantity FROM inventory WHERE number = '{$product['number']}'");
    $num_in_stock = $result->fetchAll(PDO::FETCH_ASSOC);

    echo "<tr>";
    echo "<td>{$product['number']}</td>";
    echo "<td>{$product['description']}</td>";
    echo "<td>{$product['price']}</td>";
    echo "<td>{$product['weight']}</td>";
    echo "<td><img src='{$product['pictureURL']}'></img></td>";
    echo "<td><input type='number' name='{$product['number']}' min='1' max='{$num_in_stock[0]['quantity']}'></td>"; //number input for quantity to order
    if($num_in_stock[0]['quantity'] != 0)
    {
      echo "<td><button name='add_to_cart' value='{$product['number']}' type='submit'>Add to cart</button></td>"; //add to order button
    }
    else
    {
      echo "<td><p>Item is currently out of stock.</p></td>";
    }
    echo "</tr>";
  }
  echo "</table>";

  //add item to primary shopping cart array
  if (isset($_POST["add_to_cart"]))
  {
    $selectedProduct = $_POST["add_to_cart"]; //product number
    $quantity = (int)$_POST[$selectedProduct]; //quantity of product ordered

    $productResult = $pdo->query("SELECT description, price, weight FROM Parts WHERE number = $selectedProduct;"); //retrieve remaining information on product
    $productDetails = $productResult->fetchAll();

    $productName = $productDetails[0][0]; //product description
    $productPrice = $productDetails[0][1]*$quantity; //base price of product * quantity ordered
    $productWeight = $productDetails[0][2]*$quantity; //base weight of produce * quantity ordered
    $_SESSION['totalPrice'] = $_SESSION['totalPrice'] + $productPrice;
    $_SESSION['totalWeight'] = $_SESSION['totalWeight'] + $productWeight;

    //add all information to primary shopping cart array
    $_SESSION['shopping_cart'][] = array('item_num' => $selectedProduct, 'name' => $productName, 'quantity' => $quantity, 'price' => $productPrice, 'weight' => $productWeight);
    echo "<script>alert('Item successfully added!')</script>";
  }
}
}

if(isset($_POST["remove_item"]))
{
  $itemIndex = $_POST["remove_item"]; //grab index of item to remove
  $item = $_SESSION['shopping_cart'][$itemIndex]; //grab item array

  $_SESSION['totalPrice'] = $_SESSION['totalPrice'] - $item[3];

  unset($_SESSION['shopping_cart'][$itemIndex]); //remove selected item from shopping cart
  $_SESSION['shopping_cart'] = array_values($_SESSION['shopping_cart']); //update primary shopping cart
}

if(isset($_SESSION['cart']))
{
//****************************************//
//*********DISPLAY SHOPPING CART**********//
//****************************************//
$itemPrice = 0;
$totalWeight = 0;
$fees = 0;
$totalPrice = 0;

echo "<table>";
echo "<tr><th>Product</th><th>Quantity</th><th>Price</th><th>Weight</th><th>Action</th></tr>"; //display table for primary shopping cart
foreach ($_SESSION['shopping_cart'] as $key => $item)
{
  $itemPrice = $itemPrice + $item['price']; //increment total price
  $totalWeight = $totalWeight + $item['weight']; //increment total weight

  echo "<tr>";
  echo "<td>{$item['name']}</td>";
  echo "<td>{$item['quantity']}</td>";
  echo "<td>{$item['price']}</td>";
  echo "<td>{$item['weight']}</td>";
  echo "<td><button type='submit' name='remove_item' value='$key'>Remove</button></td>"; //button to remove item from cart
  echo "</tr>";
}
echo "</table>";

foreach($weight_brackets as $bracket)
{
  if($totalWeight > $bracket['min_weight'] && $totalWeight < $bracket['max_weight'])
  {
    $fees = $bracket['charge'];
  }
}

$totalPrice = $itemPrice + $fees;
echo "<div id='checkout'>";
echo "<p>Order Item Price: $" . $itemPrice . "</p>"; //display order price based on items
echo "<p>Order Total Weight: " . $totalWeight . "</p>"; //display order weight
echo "<p>Shipping and Handling: $" . $fees . "</p>"; //display shipping and handling fees
echo "<p>Final Total: $" . $totalPrice . "</p>"; //display final order total

if(count($_SESSION['shopping_cart']) > 0 && !isset($_POST['checkout']) && !isset($_POST['Submit_Order']))
{
  echo "<p><input type='submit' name='checkout' value='Check Out'></p>";
}
echo "</div>";

//****************************************//
//***************CHECKOUT*****************//
//****************************************//
if(isset($_POST['checkout']))
{
  echo "<div id='checkout'>";
  echo "<h2>Checkout</h2>";

  echo "<p>Enter Name: ";
  echo "<input type='text' name='NameInfo' value=''>"; //enter name
  echo "  Enter Email: ";
  echo "<input type='text' name='EmailInfo' value=''></p>"; //enter email
  echo "<p>Enter Card Number: ";
  echo "<input name='CardInfo' type='tel' inputmode='numeric' pattern='[0-9\s]{13,19}' //enter credit card number
         autocomplete='cc-number' maxlength='19'
         placeholder='xxxx xxxx xxxx xxxx' value=''>";
  echo "  Enter Expiration Date: ";
  echo "<input name='CardExp' type='tel' inputmore='numeric' pattern='[0-9/]{7}' minlength='7' maxlength='7' placeholder='mm/yyyy' value=''></p>"; //enter exp date
  echo "<p>Enter Shipping Address: ";
  echo "<input type='text' name='HomeInfo' value=''></p>"; //enter shipping information
  echo "<p><input type='submit' name='Submit_Order' value='Submit Order'></p>"; //submit button
  echo "</div>";
}

if(isset($_POST['Submit_Order']))
{
  $tracking_num = rand(1000, 999999); //generate random order number

  //authorize credit card with authorization system
  $url = 'http://blitz.cs.niu.edu/CreditCard/';
  $data = array('vendor' => 'AKM20-24', 'trans' => $tracking_num, 'cc' => $_POST['CardInfo'], 'name' => $_POST['NameInfo'], 'exp' => $_POST['CardExp'], 'amount' => $totalPrice);
  $options = array('http' => array('header' => array('Content-type: application/json', 'Accept: application/json'), 'method' => 'POST', 'content'=> json_encode($data)));
  $context = stream_context_create($options);
  $result = file_get_contents($url, false, $context);
  //echo($result);

  if(strpos($result, "error")) //card information was denied by processing system
  {
    echo "<script>alert('Error in card information, unable to process order. Your cart will remain unchanged.')</script>"; //display error message
  }
  else //order succesfully sent through
  {
    echo "<script>alert('Order Submitted! An email confirmation was sent to " . $_POST['EmailInfo'] . "')</script>"; //display success message

/*
    $subject = "We Received Your Order! AKMShop";
    $message = "Thank you for your recent order " . $_POST['NameInfo'] . "!. Your unique order tracking number is " . $tracking_num . ". We will begin packing and get your order shipped out as fast as possible. Regards, The AKM Team.";
    $message = wordwrap($message, 70, "\r\n");
    $headers = array('From' => 'example@gmail.com',
                     'Reply-To' => 'example@gmail.com',
                     'X-Mailer' => 'PHP/' . phpversion());
    mail($_POST['EmailInfo'], $subject, $message, $headers);
*/

    //add order information to order array
    $pdo->query("INSERT INTO Orders (tracking_ID, name, email, card_number, card_expiration, shipping_address, total_price, total_weight, status)" .
                "VALUES ($tracking_num, '{$_POST['NameInfo']}', '{$_POST['EmailInfo']}', '{$_POST['CardInfo']}', '{$_POST['CardExp']}', '{$_POST['HomeInfo']}', " .
                "$totalPrice, $totalWeight, 'Processing')");

    foreach($_SESSION['shopping_cart'] as $item) //add each item in shopping cart to database
    {
      $amountResult = $pdo->query("SELECT quantity FROM inventory WHERE number = '{$item['item_num']}'"); //get amount in stock for each item
      $amount_in_stock = $amountResult->fetchAll(PDO::FETCH_ASSOC);

      if($amount_in_stock[0]['quantity'] >= $item['quantity']) //check if amount ordered is in stock in warehouse
      {
        $pdo->query("INSERT INTO Prod_Ordered (tracking_ID, number, description, quantity, sum_price, sum_weight)" . //add item to database
                    "VALUES ($tracking_num, '{$item['item_num']}', '{$item['name']}', '{$item['quantity']}', " .
                    "'{$item['price']}', '{$item['weight']}')");

        $pdo->query("UPDATE inventory SET quantity = quantity - '{$item['quantity']}' WHERE number = '{$item['item_num']}'"); //decrease quantity in stock
      }
      else
      {
        echo "<script>alert('Item #" . $item['item_num'] . " was overordered. " . $item['quantity'] . " were removed from your order.')</script>"; //display alert for oos item
      }
    }

    unset($_SESSION["shopping_cart"]); //reset shopping cart array after succesful order submission
    header("Refresh:0"); //refresh page
    unset($_SESSION['cart']);
    unset($_SESSION['catalog']);
  }

/*
  echo "<pre>";
  print_r($_SESSION['orders']);
  print_r($_SESSION['shopping_cart']);
  echo "</pre>";
*/

  //unset($_SESSION['orders']); //resets order array every new submission, comment out when done with testing
}
}
}
?>

</form>
</body>
</html>
