variable "railway_token" {
  description = "Railway API Token"
  type        = string
  sensitive   = true
}

variable "gh_repo" {
  description = "Repo GitHub, ej: jf2021070309/hotel"
  type        = string
}

variable "mysql_root_password" {
  description = "Contraseña root de MySQL"
  type        = string
  sensitive   = true
}

variable "cloudflare_token" {
  description = "Cloudflare API Token"
  type        = string
  sensitive   = true
}

variable "cloudflare_zone_id" {
  description = "Cloudflare Zone ID de jaimefloresdev.site"
  type        = string
}