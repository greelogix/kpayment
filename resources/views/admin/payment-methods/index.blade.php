<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KNET Payment Methods</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>KNET Payment Methods</h1>
                    <div>
                        <form action="{{ route('kpayment.admin.payment-methods.seed') }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-info">Seed Default Methods</button>
                        </form>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMethodModal">Add New</button>
                    </div>
                </div>
                
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name (EN)</th>
                                <th>Name (AR)</th>
                                <th>Description</th>
                                <th>iOS</th>
                                <th>Android</th>
                                <th>Web</th>
                                <th>Status</th>
                                <th>Sort Order</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($paymentMethods as $method)
                                <tr>
                                    <td><strong>{{ $method->code }}</strong></td>
                                    <td>{{ $method->name }}</td>
                                    <td>{{ $method->name_ar ?? '-' }}</td>
                                    <td>{{ $method->description ?? '-' }}</td>
                                    <td>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input platform-toggle" 
                                                   type="checkbox" 
                                                   data-method-id="{{ $method->id }}"
                                                   data-field="is_ios_enabled"
                                                   {{ $method->is_ios_enabled ? 'checked' : '' }}>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input platform-toggle" 
                                                   type="checkbox" 
                                                   data-method-id="{{ $method->id }}"
                                                   data-field="is_android_enabled"
                                                   {{ $method->is_android_enabled ? 'checked' : '' }}>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input platform-toggle" 
                                                   type="checkbox" 
                                                   data-method-id="{{ $method->id }}"
                                                   data-field="is_web_enabled"
                                                   {{ $method->is_web_enabled ? 'checked' : '' }}>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input status-toggle" 
                                                   type="checkbox" 
                                                   data-method-id="{{ $method->id }}"
                                                   {{ $method->is_active ? 'checked' : '' }}>
                                        </div>
                                    </td>
                                    <td>{{ $method->sort_order }}</td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editMethodModal{{ $method->id }}">
                                            Edit
                                        </button>
                                        <form action="{{ route('kpayment.admin.payment-methods.destroy', $method) }}" 
                                              method="POST" 
                                              class="d-inline"
                                              onsubmit="return confirm('Are you sure you want to delete this payment method?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center">No payment methods found. Please seed default methods.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Method Modal -->
    <div class="modal fade" id="addMethodModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('kpayment.admin.payment-methods.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Add Payment Method</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Code *</label>
                            <input type="text" name="code" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Name (EN) *</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Name (AR)</label>
                            <input type="text" name="name_ar" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Sort Order</label>
                            <input type="number" name="sort_order" class="form-control" value="0">
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="is_active" class="form-check-input" checked>
                                <label class="form-check-label">Active</label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="is_ios_enabled" class="form-check-input">
                                <label class="form-check-label">iOS Enabled</label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="is_android_enabled" class="form-check-input">
                                <label class="form-check-label">Android Enabled</label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="is_web_enabled" class="form-check-input" checked>
                                <label class="form-check-label">Web Enabled</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Method</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Platform toggles
            document.querySelectorAll('.platform-toggle').forEach(function(toggle) {
                toggle.addEventListener('change', function() {
                    const methodId = this.dataset.methodId;
                    const field = this.dataset.field;
                    const status = this.checked;

                    fetch(`{{ url('admin/kpayment/payment-methods') }}/${methodId}/toggle-status`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            field: field,
                            status: status
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            this.checked = !status;
                            alert('Failed to update status');
                        }
                    })
                    .catch(error => {
                        this.checked = !status;
                        alert('Error updating status');
                    });
                });
            });

            // Status toggle
            document.querySelectorAll('.status-toggle').forEach(function(toggle) {
                toggle.addEventListener('change', function() {
                    const methodId = this.dataset.methodId;
                    const status = this.checked;

                    fetch(`{{ url('admin/kpayment/payment-methods') }}/${methodId}/toggle-status`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            field: 'is_active',
                            status: status
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            this.checked = !status;
                            alert('Failed to update status');
                        }
                    })
                    .catch(error => {
                        this.checked = !status;
                        alert('Error updating status');
                    });
                });
            });
        });
    </script>
</body>
</html>

