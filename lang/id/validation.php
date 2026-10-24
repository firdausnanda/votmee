<?php

return [
    'after' => ':attribute harus setelah :date.',
    'array' => ':attribute harus berupa daftar.',
    'date' => ':attribute harus berupa tanggal yang valid.',
    'distinct' => ':attribute tidak boleh berulang.',
    'exists' => ':attribute yang dipilih tidak ditemukan.',
    'in' => ':attribute yang dipilih tidak valid.',
    'integer' => ':attribute harus berupa bilangan bulat.',
    'max' => [
        'array' => ':attribute tidak boleh berisi lebih dari :max item.',
        'numeric' => ':attribute tidak boleh lebih dari :max.',
        'string' => ':attribute tidak boleh lebih dari :max karakter.',
    ],
    'min' => [
        'array' => ':attribute harus berisi setidaknya :min item.',
        'numeric' => ':attribute harus setidaknya :min.',
        'string' => ':attribute harus berisi setidaknya :min karakter.',
    ],
    'required' => ':attribute wajib diisi.',
    'regex' => 'Format :attribute tidak valid.',
    'starts_with' => ':attribute harus diawali dengan :values.',
    'string' => ':attribute harus berupa teks.',
    'ulid' => ':attribute harus berupa ULID yang valid.',
    'url' => ':attribute harus berupa URL yang valid.',
    'unique' => ':attribute sudah digunakan.',
    'attributes' => [
        'name' => 'Nama',
        'username' => 'Username',
        'password' => 'Kata sandi',
        'password_confirmation' => 'Konfirmasi kata sandi',
    ],
];
