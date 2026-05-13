terraform {
  required_providers {
    railway = {
      source  = "terraform-community-providers/railway"
      version = ">= 0.6.0"
    }
    null = {
      source  = "hashicorp/null"
      version = ">= 3.0.0"
    }
  }
}

provider "railway" {
  token = var.railway_token
}

resource "railway_project" "hotel" {
  name = "hotel"
}

resource "railway_service" "mysql" {
  name         = "mysql"
  project_id   = railway_project.hotel.id
  source_image = "mysql:5.7"
}

resource "railway_service" "app" {
  name               = "hotel-app"
  project_id         = railway_project.hotel.id
  source_repo        = var.gh_repo
  source_repo_branch = "main"
}

resource "railway_tcp_proxy" "mysql_proxy" {
  environment_id   = railway_project.hotel.default_environment.id
  service_id       = railway_service.mysql.id
  application_port = 3306
}

resource "railway_variable" "mysql_root_password" {
  name           = "MYSQL_ROOT_PASSWORD"
  value          = var.mysql_root_password
  environment_id = railway_project.hotel.default_environment.id
  service_id     = railway_service.mysql.id
}

resource "railway_variable" "mysql_database" {
  name           = "MYSQL_DATABASE"
  value          = "hotel_db"
  environment_id = railway_project.hotel.default_environment.id
  service_id     = railway_service.mysql.id
}

resource "railway_variable" "mysql_host" {
  name           = "MYSQL_HOST"
  value          = "mysql.railway.internal"
  environment_id = railway_project.hotel.default_environment.id
  service_id     = railway_service.app.id
}

resource "railway_variable" "mysql_password" {
  name           = "MYSQL_PASSWORD"
  value          = var.mysql_root_password
  environment_id = railway_project.hotel.default_environment.id
  service_id     = railway_service.app.id
}

resource "railway_variable" "mysql_database_app" {
  name           = "MYSQL_DATABASE"
  value          = "hotel_db"
  environment_id = railway_project.hotel.default_environment.id
  service_id     = railway_service.app.id
}

resource "railway_variable" "mysql_user" {
  name           = "MYSQL_USER"
  value          = "root"
  environment_id = railway_project.hotel.default_environment.id
  service_id     = railway_service.app.id
}

resource "null_resource" "db_seed" {
  depends_on = [
    railway_tcp_proxy.mysql_proxy,
    railway_variable.mysql_root_password,
    railway_variable.mysql_database
  ]

  triggers = {
    proxy_host = railway_tcp_proxy.mysql_proxy.domain
    proxy_port = railway_tcp_proxy.mysql_proxy.proxy_port
    db_name    = "hotel_db"
  }

  provisioner "local-exec" {
    interpreter = ["PowerShell", "-Command"]
    command = <<EOT
Start-Sleep -Seconds 45
Get-Content "${path.module}/hotel.sql" | & "C:/xampp/mysql/bin/mysql.exe" `
  -h "${railway_tcp_proxy.mysql_proxy.domain}" `
  -P "${railway_tcp_proxy.mysql_proxy.proxy_port}" `
  -u "root" `
  -p"${var.mysql_root_password}" `
  "hotel_db"
EOT
  }
}

output "mysql_proxy" {
  value = "${railway_tcp_proxy.mysql_proxy.domain}:${railway_tcp_proxy.mysql_proxy.proxy_port}"
}

resource "railway_service_domain" "app_domain" {
  environment_id = railway_project.hotel.default_environment.id
  service_id     = railway_service.app.id
  subdomain      = "hotel-app-jaime"
}

output "app_url" {
  value = "https://${railway_service_domain.app_domain.domain}"
}