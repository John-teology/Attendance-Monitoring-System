package com.example.libraryattendance.data.remote

import com.google.gson.annotations.SerializedName

data class RawScanDto(
    @SerializedName("user_id") val userId: String? = null,
    @SerializedName("id_number") val idNumber: String? = null,
    @SerializedName("rfid_uid") val rfidUid: String? = null,
    @SerializedName("code") val code: String? = null,
    @SerializedName("timestamp") val timestamp: Long,
    @SerializedName("scan_type") val scanType: String
)