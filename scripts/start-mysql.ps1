# Start the project-local MariaDB on port 3307.
# Uses storage/mysql-data (E: drive) — not XAMPP's

$root   = Split-Path $PSScriptRoot -Parent
$ini    = Join-Path $root "storage\mysql-data\my.ini"
$mysqld = "E:\xampp\mysql\bin\mysqld.exe"

if (-not (Test-Path $mysqld)) {
    Write-Error "mysqld not found at $mysqld. Install XAMPP or edit this script."
    exit 1
}
if (-not (Test-Path $ini)) {
    Write-Error "Missing $ini. The project MySQL data directory has not been created."
    exit 1
}

Start-Process -FilePath $mysqld -ArgumentList "--defaults-file=`"$ini`"", "--console" -WindowStyle Hidden
Write-Host "BuyFirst MySQL starting on 127.0.0.1:3307"
