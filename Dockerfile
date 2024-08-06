# syntax=docker/dockerfile:1
FROM php:8.3.9-apache

# Enable mysqli
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli