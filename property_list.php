<?php
session_start();
require "includes/database_connect.php";

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : NULL;
$city_name = $_GET["city"];
$sort = isset($_GET['sort']) ? $_GET['sort'] : '';
$gender_filter = isset($_GET['gender']) ? $_GET['gender'] : '';
$max_rent = isset($_GET['max_rent']) ? intval($_GET['max_rent']) : 0;

$sql_1 = "SELECT * FROM cities WHERE name = '$city_name'";
$result_1 = mysqli_query($conn, $sql_1);
if (!$result_1) {
    echo "Something went wrong!";
    return;
}
$city = mysqli_fetch_assoc($result_1);
if (!$city) {
    echo "Sorry! We do not have any PG listed in this city.";
    return;
}
$city_id = $city['id'];

// Base query
$sql_2 = "SELECT * FROM properties WHERE city_id = $city_id";

// Filter by gender if set
if (!empty($gender_filter) && in_array($gender_filter, ['male', 'female', 'unisex'])) {
    $sql_2 .= " AND gender = '$gender_filter'";
}

// Filter by max rent if set
if ($max_rent > 0) {
    $sql_2 .= " AND rent <= $max_rent";
}

// Sorting logic
if ($sort == 'high') {
    $sql_2 .= " ORDER BY rent DESC";
} elseif ($sort == 'low') {
    $sql_2 .= " ORDER BY rent ASC";
}

$result_2 = mysqli_query($conn, $sql_2);
if (!$result_2) {
    echo "Something went wrong!";
    return;
}
$properties = mysqli_fetch_all($result_2, MYSQLI_ASSOC);

// Interested users
$sql_3 = "SELECT * 
            FROM interested_users_properties iup
            INNER JOIN properties p ON iup.property_id = p.id
            WHERE p.city_id = $city_id";
$result_3 = mysqli_query($conn, $sql_3);
if (!$result_3) {
    echo "Something went wrong!";
    return;
}
$interested_users_properties = mysqli_fetch_all($result_3, MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Best PG's in <?php echo $city_name ?> | PG Life</title>
    <?php include "includes/head_links.php"; ?>
    <link href="css/property_list.css" rel="stylesheet" />
    <style>
        .col-auto.active {
            background-color: #e6f0ff;
            border: 2px solid #007bff;
            border-radius: 5px;
        }
        .col-auto a {
            text-decoration: none;
            color: inherit;
            display: flex;
            align-items: center;
            gap: 5px;
        }
    </style>
</head>
<body>
<?php include "includes/header.php"; ?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb py-2">
        <li class="breadcrumb-item">
            <a href="index.php">Home</a>
        </li>
        <li class="breadcrumb-item active" aria-current="page">
            <?php echo $city_name; ?>
        </li>
    </ol>
</nav>

<div class="page-container">
    <div class="filter-bar row justify-content-around">
        <div class="col-auto" data-toggle="modal" data-target="#filter-modal">
            <img src="img/filter.png" alt="filter" />
            <span>Filter</span>
        </div>

        <div class="col-auto <?= ($sort == 'high') ? 'active' : '' ?>">
            <a href="?city=<?= urlencode($city_name) ?>&sort=high&gender=<?= urlencode($gender_filter) ?>&max_rent=<?= $max_rent ?>">
                <img src="img/desc.png" alt="sort-desc" />
                <span>Highest rent first</span>
            </a>
        </div>

        <div class="col-auto <?= ($sort == 'low') ? 'active' : '' ?>">
            <a href="?city=<?= urlencode($city_name) ?>&sort=low&gender=<?= urlencode($gender_filter) ?>&max_rent=<?= $max_rent ?>">
                <img src="img/asc.png" alt="sort-asc" />
                <span>Lowest rent first</span>
            </a>
        </div>
    </div>

    <?php
    foreach ($properties as $property) {
        $property_images = glob("img/properties/" . $property['id'] . "/*");
        ?>
        <div class="property-card property-id-<?= $property['id'] ?> row">
            <div class="image-container col-md-4">
                <img src="<?= $property_images[0] ?>" />
            </div>
            <div class="content-container col-md-8">
                <div class="row no-gutters justify-content-between">
                    <?php
                    $total_rating = ($property['rating_clean'] + $property['rating_food'] + $property['rating_safety']) / 3;
                    $total_rating = round($total_rating, 1);
                    ?>
                    <div class="star-container" title="<?= $total_rating ?>">
                        <?php
                        $rating = $total_rating;
                        for ($i = 0; $i < 5; $i++) {
                            if ($rating >= $i + 0.8) {
                                echo '<i class="fas fa-star"></i>';
                            } elseif ($rating >= $i + 0.3) {
                                echo '<i class="fas fa-star-half-alt"></i>';
                            } else {
                                echo '<i class="far fa-star"></i>';
                            }
                        }
                        ?>
                    </div>
                    <div class="interested-container">
                        <?php
                        $interested_users_count = 0;
                        $is_interested = false;
                        foreach ($interested_users_properties as $interested_user_property) {
                            if ($interested_user_property['property_id'] == $property['id']) {
                                $interested_users_count++;
                                if ($interested_user_property['user_id'] == $user_id) {
                                    $is_interested = true;
                                }
                            }
                        }

                        if ($is_interested) {
                            echo '<i class="is-interested-image fas fa-heart" property_id="' . $property['id'] . '"></i>';
                        } else {
                            echo '<i class="is-interested-image far fa-heart" property_id="' . $property['id'] . '"></i>';
                        }
                        ?>
                        <div class="interested-text">
                            <span class="interested-user-count"><?= $interested_users_count ?></span> interested
                        </div>
                    </div>
                </div>
                <div class="detail-container">
                    <div class="property-name"><?= $property['name'] ?></div>
                    <div class="property-address"><?= $property['address'] ?></div>
                    <div class="property-gender">
                        <?php
                        if ($property['gender'] == "male") {
                            echo '<img src="img/male.png" />';
                        } elseif ($property['gender'] == "female") {
                            echo '<img src="img/female.png" />';
                        } else {
                            echo '<img src="img/unisex.png" />';
                        }
                        ?>
                    </div>
                </div>
                <div class="row no-gutters">
                    <div class="rent-container col-6">
                        <div class="rent">₹ <?= number_format($property['rent']) ?>/-</div>
                        <div class="rent-unit">per month</div>
                    </div>
                    <div class="button-container col-6">
                        <a href="property_detail.php?property_id=<?= $property['id'] ?>" class="btn btn-primary">View</a>
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>

    <?php if (count($properties) == 0) { ?>
        <div class="no-property-container"><p>No PG to list</p></div>
    <?php } ?>
</div>

<!-- Filter Modal -->
<div class="modal fade" id="filter-modal" tabindex="-1" role="dialog" aria-labelledby="filterModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form method="GET" action="">
            <input type="hidden" name="city" value="<?= htmlspecialchars($city_name) ?>">
            <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">

            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Filter Properties</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>

                <div class="modal-body">
                    <label>Gender:</label>
                    <select name="gender" class="form-control">
                        <option value="">Any</option>
                        <option value="male" <?= ($gender_filter == 'male') ? 'selected' : '' ?>>Male</option>
                        <option value="female" <?= ($gender_filter == 'female') ? 'selected' : '' ?>>Female</option>
                        <option value="unisex" <?= ($gender_filter == 'unisex') ? 'selected' : '' ?>>Unisex</option>
                    </select>

                    <label class="mt-3">Max Rent:</label>
                    <input type="number" name="max_rent" value="<?= $max_rent ?>" class="form-control" placeholder="Enter maximum rent">
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php
include "includes/signup_modal.php";
include "includes/login_modal.php";
include "includes/footer.php";
?>
<script type="text/javascript" src="js/property_list.js"></script>
</body>
</html>
