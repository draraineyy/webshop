<?php
session_start();

if(!isset($_SESSION['customer_id'])){
    header('Location: viewlogin.php');
    exit;
}

require_once("../db.php");

$customerId=(int)$_SESSION['customer_id'];

if(empty($_SESSION['csrf'])){
    $_SESSION['csrf']=bin2hex(random_bytes(32));
}
$csrf=$_SESSION['csrf'];

//Filter einlesen & validieren
$from=isset($_GET['from'])?trim($_GET['from']):'';
$to=isset($_GET['to'])?trim($_GET['to']):'';
$activity=isset($_GET['activity'])?trim($_GET['activity']):'';

//Simple Validierung Datum
$validDate=function(string$d):bool{
    if($d==='')return false;
    $parts=explode('-', $d);
    return (count($parts)===3&&checkdate((int)$parts[1], (int)$parts[2], (int)$parts[0]));
};
if($from!==''&&!$validDate($from))$from='';
if($to!==''&&!$validDate($to))$to='';

//Standard: Letzten 90 Tage, wenn kein Filter gesetzt
if($from===''&&$to===''){
    $from=date('Y-m-d', strtotime('-90 days'));
    $to=date('Y-m-d');
}

// Aktivitäten für Dropdown laden
$stmtActs=$pdo->prepare("SELECT DISTINCT activity FROM points WHERE customer_id=? ORDER BY activity ASC");
$stmtActs->execute([$customerId]);
$activities=$stmtActs->fetchAll(PDO::FETCH_COLUMN)?:[];

// Abfrage für Liste + Summen aufbauen
$where="WHERE customer_id =?";
$params=[$customerId];

if($from!==''){
    $where .=" AND `date` >= ?";
    $params[]=$from . " 00:00:00";
}
if($to!==''){
    $where .=" AND `date` <= ?";
    $params[]=$to . " 23:59:59";
}
if($activity!==''){
    $where .= " AND activity = ?";
    $params[]=$activity;
}

//Summen im Zeitraum
$stmtSum=$pdo->prepare("SELECT COALESCE(SUM(points),0) AS sum_points FROM points $where");
$stmtSum->execute($params);
$periodSum=(int)$stmtSum->fetchColumn();

//Lifetime-Summe
$stmtLife=$pdo->prepare("SELECT COALESCE(SUM(points), 0) FROM points WHERE customer_id = ?");
$stmtLife->execute([$customerId]);
$lifetimeSum = (int)$stmtLife->fetchColumn();


//Einträge (limitiert, neuesten zuerst)
$stmtList=$pdo->prepare("
    SELECT activity, points, `date`
    FROM points
    $where
    ORDER BY `date` DESC, activity ASC
");
$stmtList->execute($params);
$rows=$stmtList->fetchAll(PDO::FETCH_ASSOC);

//Hilfsformat
$fmtNum=fn(int $n)=> number_format($n, 0, ',', '.');
?>
<!DOCTYPE html>
<html lang="de">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Punkteübersicht</title>
        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <!-- Font Awesome -->
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
        <!-- Eigene CSS -->
        <link rel="stylesheet" href="../css/style.css">
    </head>

    <body class="bg-light">
        <div class="container py-4">
            <div class="row justify-content-center">
                <div class="col-12 col-xl-10 col-xxl-8">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="mb-0"><i class="fa-solid fa-star text-warning"></i>Meine Punkte</h2>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="viewaccount.php" class="btn btn-outline-secondary me-2">
                            <i class="fa-solid fa-house"></i> Mein Account
                        </a>
                        <a href="viewproducts.php" class="btn btn-outline-secondary me-2">
                            <i class="fa-solid fa-house"></i> Artikelübersicht
                        </a>
                    </div>
                    <!--Filter-->
                    <form method="get" class="row g-2 mb-3">
                        <div class="col-md-3">
                            <label for="from" class="form-label">Von</label>
                            <input type="date" id="from" name="from" class="form-control" value="<?=htmlspecialchars($from)?>">
                        </div>
                        <div class="col-md-3">
                            <label for="to" class="form-label">Bis</label>
                            <input type="date" id="to" name="to" class="form-control" value="<?=htmlspecialchars($to)?>">
                        </div>
                        <div class="col-md-3">
                            <label for="activity" class="form-label">Aktivität</label>
                            <select id="activity" name="activity" class="form-select">
                                <option value="">Alle</option>
                                <?php foreach ($activities as $act):?>
                                    <option value="<?=htmlspecialchars($act)?>" <?=$activity===$act?'selected':''?>>
                                        <?=htmlspecialchars($act)?>
                                    </option>
                                <?php endforeach;?>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fa-solid fa-filter"></i>Filtern
                            </button>
                        </div>
                    </form>

                    <!--Statistiken-->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="stat-pill">
                                <strong>Summe im Zeitraum: </strong>
                                <span class="<?=$periodSum >=0?'points-pos':'points-neg'?>">
                                    <?=$periodSum >=0? '+':''?><?=$fmtNum($periodSum)?>
                                </span>
                                Punkte
                            </div>
                        </div>
                        <div class="col-md-6 text-md-end mt-2 mt-md-0">
                            <div class="stat-pill d-inline-block">
                                <strong>Gesamtsumme:</strong>
                                <span class="<?=$lifetimeSum>=0?'points-pos':'points-neg'?>">
                                    <?=$lifetimeSum >=0?'+':''?><?=$fmtNum($lifetimeSum)?>
                                </span>
                                Punkte
                            </div>
                        </div>
                    </div>

                    <!-- Tabelle -->
                    <div class="card points-card">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 18rem;">Datum/Zeit</th>
                                            <th>Aktivität</th>
                                            <th class="text-end" style="width: 10rem;">Punkte</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(empty($rows)):?>
                                            <tr>
                                                <td colspan="3" class="text-muted p-4">Keine Punkte im gewählten Zeitraum.</td>
                                            </tr>
                                        <?php else:?>
                                            <?php foreach($rows as $r):?>
                                                <?php
                                                    $pts=(int)$r['points'];
                                                    $cls=$pts>=0?'points-pos':'points-neg';
                                                    $sign=$pts>=0?'+':'';
                                                ?>
                                                <tr>
                                                    <td><?=htmlspecialchars($r['date'])?></td>
                                                    <td><?=htmlspecialchars($r['activity'])?></td>
                                                    <td class="text-end <?=$cls?>">
                                                        <?=$sign .$fmtNum($pts)?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif;?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>