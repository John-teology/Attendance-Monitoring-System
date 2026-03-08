package com.example.libraryattendance.data.remote

import com.google.gson.annotations.SerializedName

data class UserDto(
    @SerializedName("id") val id: Long,
    @SerializedName("full_name") val fullName: String,
    @SerializedName("id_number") val idNumber: String,
    @SerializedName("user_type") val userType: String,
    @SerializedName("qr_code") val qrCode: String?,
    @SerializedName("rfid_uid") val rfidUid: String?,
    @SerializedName("status") val status: String?,
    @SerializedName("id_expiration_date") val idExpirationDate: String?,
    @SerializedName("department") val department: String?,
    @SerializedName("course") val course: String?,
    @SerializedName("year_level") val yearLevel: String?,
    @SerializedName("designation") val designation: String?,
    @SerializedName("profile_picture_url") val profilePictureUrl: String?
)

data class UserListResponse(
    @SerializedName("success") val success: Boolean,
    @SerializedName("users") val users: List<UserDto>
)