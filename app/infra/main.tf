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
    cloudflare = {
      source  = "cloudflare/cloudflare"
      version = "~> 4.0"
    }
  }
}

provider "railway" {
  token = var.railway_token
}

provider "cloudflare" {
  api_token = var.cloudflare_token
}

resource "railway_project" "hotel" {
  name = "hotel"
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

resource "railway_custom_domain" "hotel" {
  domain         = "hotel.jaimefloresdev.site"
  environment_id = railway_project.hotel.default_environment.id
  service_id     = railway_service.app.id
}

resource "railway_variable" "mysql_host" {
  name           = "MYSQL_HOST"
  value          = "MySQL.railway.internal"
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

resource "railway_variable" "app_env" {
  name           = "APP_ENV"
  value          = "production"
  environment_id = railway_project.hotel.default_environment.id
  service_id     = railway_service.app.id
}

resource "railway_variable" "mysql_user" {
  name           = "MYSQL_USER"
  value          = "root"
  environment_id = railway_project.hotel.default_environment.id
  service_id     = railway_service.app.id
}

resource "railway_tcp_proxy" "mysql_proxy" {
  environment_id   = railway_project.hotel.default_environment.id
  service_id       = railway_service.mysql.id
  application_port = 3306
}

resource "cloudflare_record" "hotel" {
  zone_id         = var.cloudflare_zone_id
  name            = "hotel"
  content         = railway_service_domain.app_domain.domain
  type            = "CNAME"
  proxied         = true
  allow_overwrite = true

  depends_on = [railway_custom_domain.hotel]
}

output "app_url" {
  value = "https://hotel.jaimefloresdev.site"
}

output "app_url_railway" {
  value = "https://${railway_service_domain.app_domain.domain}"
}

output "mysql_proxy_domain" {
  value = railway_tcp_proxy.mysql_proxy.domain
}

output "mysql_proxy_port" {
  value = railway_tcp_proxy.mysql_proxy.proxy_port
}