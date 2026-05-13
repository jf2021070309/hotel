terraform {
  cloud {
    organization = "jaime-hotel"
    workspaces {
      name = "hotel"
    }
  }

  required_providers {
    railway = {
      source  = "terraform-community-providers/railway"
      version = ">= 0.6.0"
    }
    time = {
      source  = "hashicorp/time"
      version = ">= 0.9.0"
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

provider "time" {}

resource "railway_project" "hotel" {
  name         = "hotel"
  workspace_id = "9437db35-bbad-4338-a688-8e79ad39cc8e"
}

resource "railway_service" "mysql" {
  name         = "MySQL"
  project_id   = railway_project.hotel.id
  source_image = "mysql:5.7"
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

  depends_on = [railway_variable.mysql_root_password]
}

resource "railway_service" "app" {
  name               = "hotel-app"
  project_id         = railway_project.hotel.id
  source_repo        = var.gh_repo
  source_repo_branch = "main"
}

resource "railway_service_domain" "app_domain" {
  environment_id = railway_project.hotel.default_environment.id
  service_id     = railway_service.app.id
  subdomain      = "hotel-app-jaime"
}

resource "railway_variable" "mysql_host" {
  name           = "MYSQL_HOST"
  value          = "MySQL.railway.internal"
  environment_id = railway_project.hotel.default_environment.id
  service_id     = railway_service.app.id
}

resource "time_sleep" "wait_1" {
  depends_on      = [railway_variable.mysql_host]
  create_duration = "15s"
}

resource "railway_variable" "mysql_password" {
  name           = "MYSQL_PASSWORD"
  value          = var.mysql_root_password
  environment_id = railway_project.hotel.default_environment.id
  service_id     = railway_service.app.id

  depends_on = [time_sleep.wait_1]
}

resource "time_sleep" "wait_2" {
  depends_on      = [railway_variable.mysql_password]
  create_duration = "15s"
}

resource "railway_variable" "mysql_database_app" {
  name           = "MYSQL_DATABASE"
  value          = "hotel_db"
  environment_id = railway_project.hotel.default_environment.id
  service_id     = railway_service.app.id

  depends_on = [time_sleep.wait_2]
}

resource "time_sleep" "wait_3" {
  depends_on      = [railway_variable.mysql_database_app]
  create_duration = "15s"
}

resource "railway_variable" "app_env" {
  name           = "APP_ENV"
  value          = "production"
  environment_id = railway_project.hotel.default_environment.id
  service_id     = railway_service.app.id

  depends_on = [time_sleep.wait_3]
}

resource "time_sleep" "wait_4" {
  depends_on      = [railway_variable.app_env]
  create_duration = "15s"
}

resource "railway_variable" "mysql_user" {
  name           = "MYSQL_USER"
  value          = "root"
  environment_id = railway_project.hotel.default_environment.id
  service_id     = railway_service.app.id

  depends_on = [time_sleep.wait_4]
}

resource "railway_tcp_proxy" "mysql_proxy" {
  environment_id   = railway_project.hotel.default_environment.id
  service_id       = railway_service.mysql.id
  application_port = 3306
}

resource "null_resource" "db_seed" {
  depends_on = [
    railway_tcp_proxy.mysql_proxy,
    railway_variable.mysql_root_password,
    railway_variable.mysql_database
  ]

  provisioner "local-exec" {
    command = "sleep 30 && mysql -h ${railway_tcp_proxy.mysql_proxy.domain} -P ${railway_tcp_proxy.mysql_proxy.proxy_port} -u root -p${var.mysql_root_password} hotel_db < ${path.module}/hotel.sql"
  }
}

output "app_url" {
  value = "https://${railway_service_domain.app_domain.domain}"
}

output "mysql_proxy" {
  value = "${railway_tcp_proxy.mysql_proxy.domain}:${railway_tcp_proxy.mysql_proxy.proxy_port}"
}