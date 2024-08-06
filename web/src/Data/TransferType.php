<?php

/**
 * Represents the different types of transfers
 */

namespace EasyTransfer\Data;

enum TransferType: string {
	case URL = 'u';
	case TEXT = 't';
}