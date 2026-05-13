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
