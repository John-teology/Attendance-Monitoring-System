package com.example.libraryattendance.data.local

import androidx.room.ColumnInfo

data class SyncLogDto(
    @ColumnInfo(name = "id") val id: Long,
    @ColumnInfo(name = "timestamp") val timestamp: Long,
    @ColumnInfo(name = "scan_type") val scanType: String,
    @ColumnInfo(name = "id_number") val idNumber: String
)