package com.example.libraryattendance.data.local

import androidx.room.ColumnInfo
import androidx.room.Entity
import androidx.room.PrimaryKey

@Entity(tableName = "users")
data class User(
    @PrimaryKey(autoGenerate = false) val id: Long,
    @ColumnInfo(name = "user_type") val userType: String, // student/faculty
    @ColumnInfo(name = "full_name") val fullName: String,
    @ColumnInfo(name = "id_number") val idNumber: String,
    @ColumnInfo(name = "qr_code") val qrCode: String?,
    @ColumnInfo(name = "rfid_uid") val rfidUid: String?,
    @ColumnInfo(name = "status") val status: String? = "active",
    @ColumnInfo(name = "id_expiration_date") val idExpirationDate: String? = null,
    @ColumnInfo(name = "department") val department: String? = null,
    @ColumnInfo(name = "course") val course: String? = null,
    @ColumnInfo(name = "year_level") val yearLevel: String? = null,
    @ColumnInfo(name = "designation") val designation: String? = null,
    @ColumnInfo(name = "profile_picture") val profilePicture: String? = null
)
