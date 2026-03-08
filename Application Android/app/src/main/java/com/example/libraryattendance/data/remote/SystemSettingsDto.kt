package com.example.libraryattendance.data.remote

import com.google.gson.annotations.SerializedName

data class SystemSettingsDto(
    @SerializedName("school_name") val schoolName: String?,
    @SerializedName("school_logo_url") val schoolLogoUrl: String?,
    @SerializedName("app_background_image") val appBackgroundImage: String?,
    @SerializedName("school_name_color") val schoolNameColor: String?,
    @SerializedName("button_bg_color") val buttonBgColor: String?,
    @SerializedName("body_bg_color") val bodyBgColor: String?,
    @SerializedName("font_style") val fontStyle: String?,
    @SerializedName("card_transparency") val cardTransparency: Int?,
    @SerializedName("button_transparency") val buttonTransparency: Int?,
    @SerializedName("icon_color") val iconColor: String?,
    @SerializedName("volume_level") val volumeLevel: Int?
)