const makeQRImg = ( URL ) => {
	const img = document.createElement( 'img' );
	// Figure out size
	let size = '250x250';
	if ( URL.length > 400 ) {
		size = '500x500';
	} else if ( URL.length > 200 ) {
		size = '300x300';
	}
	img.setAttribute(
		'src',
		'https://api.qrserver.com/v1/create-qr-code/?size=' + size + '&data=' + encodeURIComponent( URL )
	);
	return img;
}

// Need to wait for document to load
document.addEventListener( 'DOMContentLoaded', () => {

	const typeDisplay = document.getElementById( 'et-transfer-type-display' );
	const transferContent = document.getElementById( 'et-transfer-content' );
	const genBtn = document.getElementById( 'et-transfer-generate' );
	const qrDisplay = document.getElementById( 'et-transfer-QR' );

	if ( genBtn === null ) {
		// Successful submission of saving, no form shown, nothing to do
		return;
	}
	genBtn.addEventListener( 'click', () => {
		if ( transferContent.value.trim() === '' ) {
			return;
		}
		let targetURL = transferContent.value;
		if ( typeDisplay.innerText === 'Text' ) {
			const currUrl = new URL( location.href );
			// Path might not be from the root of the domain, e.g. on SiteGround
			currUrl.pathname = currUrl.pathname.replace( '/transfer.php', '/text.php' );
			currUrl.searchParams.append( 'text', targetURL );
			targetURL = currUrl.toString();
		}
		qrDisplay.replaceChildren( makeQRImg( targetURL ) );
	} );

	const radioURL = document.getElementById( 'et-type-url' );
	const radioText = document.getElementById( 'et-type-text' );

	radioURL.addEventListener( 'input', () => typeDisplay.innerText = 'URL' );
	radioText.addEventListener( 'input', () => typeDisplay.innerText = 'Text' );

} );