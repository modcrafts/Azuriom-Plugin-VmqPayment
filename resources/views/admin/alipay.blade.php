@include('shop::admin.elements.select')

<div class="row g-3">
    <div class="mb-3 col-md-6">
        <label class="form-label" for="hostInput">远端地址</label>
        <input type="text" class="form-control @error('host') is-invalid @enderror" id="hostInput" name="host" value="{{ old('host', $gateway->data['host'] ?? '') }}" required placeholder="example.com">

        @error('host')
        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
        @enderror
    </div>

    <div class="mb-3 col-md-6">
        <label class="form-label" for="keyInput">通信密钥</label>
        <input type="text" class="form-control @error('secret') is-invalid @enderror" id="keyInput" name="secret" value="{{ old('secret', $gateway->data['secret'] ?? '') }}" required placeholder="通信密钥">

        @error('secret')
        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
        @enderror
    </div>
</div>
