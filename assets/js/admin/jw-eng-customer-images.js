document.addEventListener( 'click', ( event ) => {
	const multipleMediaButton = event.target.closest( '.jw-eng-ci-media-multiple' );
	if ( multipleMediaButton ) {
		event.preventDefault();
		const preview = document.getElementById( multipleMediaButton.dataset.preview );
		const renderImage = wp.template( 'jw-eng-ci-selected-image' );
		const frame = wp.media( {
			title: jwEngCustomerImages.title,
			button: { text: jwEngCustomerImages.useImage },
			library: { type: 'image' },
			multiple: true,
		} );
		frame.on( 'select', () => {
			const attachments = frame.state().get( 'selection' ).map( ( item ) => item.toJSON() );
			preview.innerHTML = attachments.map( ( attachment ) => renderImage( {
				id: attachment.id,
				url: attachment.url,
				filename: attachment.filename,
			} ) ).join( '' );
		} );
		frame.on( 'open', () => {
			preview.querySelectorAll( 'input[name="image_ids[]"]' ).forEach( ( input ) => {
				frame.state().get( 'selection' ).add( wp.media.attachment( Number( input.value ) ) );
			} );
		} );
		frame.open();
		return;
	}

	const clearButton = event.target.closest( '.jw-eng-ci-media-clear' );
	if ( clearButton ) {
		document.getElementById( clearButton.dataset.target ).value = '0';
		document.querySelector( `[data-preview-for="${ clearButton.dataset.target }"]` ).removeAttribute( 'src' );
		return;
	}

	const mediaButton = event.target.closest( '.jw-eng-ci-media' );
	if ( mediaButton ) {
		event.preventDefault();
		const input = document.getElementById( mediaButton.dataset.target );
		const preview = document.querySelector( `[data-preview-for="${ mediaButton.dataset.target }"]` );
		const frame = wp.media( { title: jwEngCustomerImages.title, button: { text: jwEngCustomerImages.useImage }, library: { type: 'image' }, multiple: false } );
		frame.on( 'select', () => {
			const attachment = frame.state().get( 'selection' ).first().toJSON();
			input.value = attachment.id;
			preview.src = attachment.url;
		} );
		frame.open();
		return;
	}

	const confirmButton = event.target.closest( 'button[data-confirm]' );
	if ( confirmButton && ! window.confirm( confirmButton.dataset.confirm ) ) {
		event.preventDefault();
	}
} );


const bulkForm = document.getElementById( 'jw-eng-ci-bulk-delete' );
const checkAll = document.querySelector( '.jw-eng-ci-check-all' );
const imageCheckboxes = document.querySelectorAll( 'input[name="image_record_ids[]"][form="jw-eng-ci-bulk-delete"]' );
const syncSelection = () => {
	const selected = Array.from( imageCheckboxes ).filter( ( checkbox ) => checkbox.checked ).length;
	if ( checkAll ) {
		checkAll.checked = selected > 0 && selected === imageCheckboxes.length;
		checkAll.indeterminate = selected > 0 && selected < imageCheckboxes.length;
	}
	if ( bulkForm ) {
		bulkForm.querySelector( 'button[type="submit"]' ).disabled = selected === 0;
	}
};
checkAll?.addEventListener( 'change', ( event ) => {
	imageCheckboxes.forEach( ( checkbox ) => {
		checkbox.checked = event.target.checked;
	} );
	syncSelection();
} );
imageCheckboxes.forEach( ( checkbox ) => checkbox.addEventListener( 'change', syncSelection ) );
bulkForm?.addEventListener( 'submit', ( event ) => {
	if ( ! Array.from( imageCheckboxes ).some( ( checkbox ) => checkbox.checked ) || ! window.confirm( bulkForm.dataset.confirm ) ) {
		event.preventDefault();
	}
} );
syncSelection();

document.querySelector( '#jw-eng-ci-image-category-filter' )?.addEventListener( 'change', ( event ) => {
	const categoryId = event.target.value;
	const subcategory = document.querySelector( '#jw-eng-ci-image-subcategory-filter' );
	if ( ! subcategory ) {
		return;
	}
	subcategory.value = '0';
	subcategory.querySelectorAll( 'option[data-category-id]' ).forEach( ( option ) => {
		option.hidden = '0' !== categoryId && option.dataset.categoryId !== categoryId;
	} );
} );
