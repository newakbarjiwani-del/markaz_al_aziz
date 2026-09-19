# Filters and PDF exports

- Nested query filters use `filter[...]`; whitelist/remap with `FilterHandler::resolveFilters` (or Serang equivalents on the controller).
- Strip values `all` / empty. Force school scope from `users.sekolah_id` via `FilterHandler::applySekolahScope(..., $column = 'sekolah_id')`.
- Server PDFs: DomPDF + `@extends('layouts.export.kop_file')` (or Serang export layouts); pass filter-derived labels into PDF views.
- DataTables export: hybrid `{display, raw, type}` cells; row cap ~3000; lock export button while running.
