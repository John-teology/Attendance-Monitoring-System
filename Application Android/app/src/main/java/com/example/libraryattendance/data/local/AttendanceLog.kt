package com.example.libraryattendance.data.local

import androidx.room.ColumnInfo
import androidx.room.Entity
import androidx.room.ForeignKey
import androidx.room.Index
import androidx.room.PrimaryKey
import com.google.gson.annotations.SerializedName

@Entity(
    tableName = "attendance_logs",
    foreignKeys = [
        ForeignKey(
            entity = User::class,
            parentColumns = ["id"],
            childColumns = ["user_id"],
            onDelete = ForeignKey.CASCADE
        )
    ],
    indices = [Index(value = ["user_id"])]
)
data class AttendanceLog(
    @PrimaryKey(autoGenerate = true) 
    @SerializedName("id")
    val id: Long = 0,

    @ColumnInfo(name = "user_id") 
    @SerializedName("user_id")
    val userId: Long,

    @ColumnInfo(name = "scan_type") 
    @SerializedName("scan_type")
    val scanType: String, // QR/RFID

    @ColumnInfo(name = "timestamp") 
    @SerializedName("timestamp")
    val timestamp: Long,

    @ColumnInfo(name = "entry_type")
    val entryType: String? = null,

    @ColumnInfo(name = "sync_status") 
    val syncStatus: Boolean = false
)
