<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $data['title'] ?? 'Weibull Simulation' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- <link rel="stylesheet" href="../../resources/css/style.min.css"> -->
    <style>
        :root {
            --primary: #2c3e50;
            --success: #27ae60;
            --danger: #e74c3c;
            --warning: #f39c12;
            --info: #3498db;
        }
        
        body { 
            background: #f0f2f5; 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .navbar { 
            background: var(--primary) !important; 
            box-shadow: 0 2px 8px rgba(0,0,0,.1);
        }
        
        .card {
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,.08);
            margin-bottom: 20px;
            border-radius: 12px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,.12);
        }
        
        .card-header {
            background: #fff;
            border-bottom: 1px solid #e9ecef;
            padding: 16px 20px;
            font-weight: 600;
            font-size: 1rem;
            border-radius: 12px 12px 0 0 !important;
        }
        
        .card-body { 
            padding: 20px; 
        }
        
        .btn-primary {
            background: var(--info);
            border: none;
        }
        
        .btn-primary:hover {
            background: #2980b9;
        }
        
        .table th {
            border-top: none;
            color: #6c757d;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .progress {
            background: #e9ecef;
            border-radius: 10px;
        }
        
        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.75rem;
        }
        
        .footer {
            background: #fff;
            padding: 20px;
            text-align: center;
            color: #6c757d;
            font-size: 0.85rem;
            margin-top: 40px;
            border-top: 1px solid #e9ecef;
        }
        
        @media (max-width: 768px) {
            .card-header { font-size: 0.9rem; }
            .table { font-size: 0.85rem; }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark mb-4">
        <div class="container-fluid px-4">
            <span class="navbar-brand">
                <i class="fas fa-chart-line me-2"></i>
                <strong>Weibull Simulation</strong>
            </span>
            <div>
                <a href="/simulation" class="btn btn-sm btn-outline-light me-2 <?= str_contains($_SERVER['REQUEST_URI'], '/simulation') ? 'active' : '' ?>">
                    <i class="fas fa-microchip me-1"></i>Симуляция
                </a>
                <a href="/statistics" class="btn btn-sm btn-outline-light <?= str_contains($_SERVER['REQUEST_URI'], '/statistics') ? 'active' : '' ?>">
                    <i class="fas fa-chart-bar me-1"></i>Статистика
                </a>
            </div>
        </div>
    </nav>
    
    <div class="container-fluid px-4">
        <?= $content ?>
    </div>
    
    <div class="footer">
        Kot_baun
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</body>
</html>